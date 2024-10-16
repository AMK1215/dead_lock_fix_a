<?php

namespace App\Http\Controllers\Admin\Agent;

use App\Enums\TransactionName;
use App\Enums\TransactionType;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\AgentRequest;
use App\Http\Requests\TransferLogRequest;
use App\Models\Admin\TransferLog;
use App\Models\PaymentType;
use App\Models\User;
use App\Services\WalletService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Contracts\DataTable;
use Yajra\DataTables\Facades\DataTables;

class AgentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private const AGENT_ROLE = 2;

    public function index()
    {
        abort_if(
            Gate::denies('agent_index'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );
        //kzt
        $users = User::with('roles')
            ->whereHas('roles', function ($query) {
                $query->where('role_id', self::AGENT_ROLE);
            })
            ->where('agent_id', auth()->id())
            ->orderBy('id', 'desc')
            ->get();

        //kzt
        return view('admin.agent.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(
            Gate::denies('agent_create'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );
        $agent_name = $this->generateRandomString();
        $referral_code = $this->generateReferralCode();
        $paymentTypes = PaymentType::all();

        return view('admin.agent.create', compact('agent_name', 'referral_code', 'paymentTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AgentRequest $request)
    {
        abort_if(
            Gate::denies('agent_create'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );
        $master = Auth::user();
        $inputs = $request->validated();

        if (isset($inputs['amount']) && $inputs['amount'] > $master->balanceFloat) {
            throw ValidationException::withMessages([
                'amount' => 'Insufficient balance for transfer.',
            ]);
        }
        $transfer_amount = $inputs['amount'];

        if ($request->hasFile('agent_logo')) {
            $image = $request->file('agent_logo');
            $ext = $image->getClientOriginalExtension();
            $filename = uniqid('logo_').'.'.$ext;
            $image->move(public_path('assets/img/sitelogo/'), $filename);
            $request->agent_logo = $filename;
        }

        $agent = User::create([
            'user_name' => $request->user_name,
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => Hash::make($inputs['password']),
            'agent_id' => Auth::id(),
            'type' => UserType::Agent,
            'agent_logo' => $request->agent_logo,
            'referral_code' => $request->referral_code,
            'line_id' => $request->line_id,
            'payment_type_id' => $request->payment_type_id,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'commission' => $request->commission ?? 0.00,
        ]);

        $agent->roles()->sync(self::AGENT_ROLE);

        if (isset($inputs['amount'])) {
            app(WalletService::class)->transfer($master, $agent, $inputs['amount'], TransactionName::CreditTransfer);
        }

        return redirect()->back()
            ->with('success', 'Agent created successfully')
            ->with('password', $request->password)
            ->with('username', $agent->user_name)
            ->with('amount', $transfer_amount);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        abort_if(
            Gate::denies('agent_show'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $user_detail = User::find($id);

        return view('admin.agent.show', compact('user_detail'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        abort_if(
            Gate::denies('agent_edit') || ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $agent = User::find($id);
        $paymentTypes = PaymentType::all();

        return view('admin.agent.edit', compact('agent', 'paymentTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $param = $request->validate([
            'name' => 'required|string',
            'phone' => ['nullable', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'unique:users,phone,'.$id],
            'payment_type_id' => 'required|exists:payment_types,id',
            'account_number' => 'required|string',
            'account_name' => 'required|string',
            'line_id' => 'nullable',
            'commission' => 'nullable',
        ]);

        $user = User::find($id);
        if ($request->file('agent_logo')) {
            File::delete(public_path('assets/img/sitelogo/'.$user->agent_logo));
            $image = $request->file('agent_logo');
            $ext = $image->getClientOriginalExtension();
            $filename = uniqid('agent_logo').'.'.$ext;
            $image->move(public_path('assets/img/sitelogo/'), $filename);
            $param['agent_logo'] = $filename;
        }

        $user->update($param);

        return redirect()->back()
            ->with('success', 'Agent Updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function getCashIn(string $id)
    {
        abort_if(
            Gate::denies('make_transfer'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $agent = User::find($id);

        return view('admin.agent.cash_in', compact('agent'));
    }

    public function getCashOut(string $id)
    {
        abort_if(
            Gate::denies('make_transfer'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        // Assuming $id is the user ID
        $agent = User::findOrFail($id);

        return view('admin.agent.cash_out', compact('agent'));
    }

    public function makeCashIn(TransferLogRequest $request, $id)
    {

        abort_if(
            Gate::denies('make_transfer') || ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        try {
            $inputs = $request->validated();
            $agent = User::findOrFail($id);
            $admin = Auth::user();
            $cashIn = $inputs['amount'];
            if ($cashIn > $admin->balanceFloat) {
                throw new \Exception('You do not have enough balance to transfer!');
            }

            // Transfer money
            app(WalletService::class)->transfer($admin, $agent, $request->validated('amount'), TransactionName::CreditTransfer);

            return redirect()->back()->with('success', 'Money fill request submitted successfully!');
        } catch (Exception $e) {

            session()->flash('error', $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function makeCashOut(TransferLogRequest $request, string $id)
    {

        abort_if(
            Gate::denies('make_transfer') || ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        try {
            $inputs = $request->validated();

            $agent = User::findOrFail($id);
            $admin = Auth::user();
            $cashOut = $inputs['amount'];

            if ($cashOut > $agent->balanceFloat) {

                return redirect()->back()->with('error', 'You do not have enough balance to transfer!');
            }

            // Transfer money
            app(WalletService::class)->transfer($agent, $admin, $request->validated('amount'), TransactionName::DebitTransfer);

            return redirect()->back()->with('success', 'Money fill request submitted successfully!');
        } catch (Exception $e) {

            session()->flash('error', $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage());
        }

        // Redirect back with a success message
        return redirect()->back()->with('success', 'Money fill request submitted successfully!');
    }

    public function getTransferDetail($id)
    {
        abort_if(
            Gate::denies('make_transfer') || ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );
        $transfer_detail = TransferLog::where('from_user_id', $id)
            ->orWhere('to_user_id', $id)
            ->get();

        return view('admin.agent.transfer_detail', compact('transfer_detail'));
    }

    private function generateRandomString()
    {
        $randomNumber = mt_rand(10000000, 99999999);

        return 'LKM'.$randomNumber;
    }

    public function banAgent($id)
    {
        abort_if(
            ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $user = User::find($id);
        $user->update(['status' => $user->status == 1 ? 0 : 1]);
        if (Auth::check() && Auth::id() == $id) {
            Auth::logout();
        }

        return redirect()->back()->with(
            'success',
            'User '.($user->status == 1 ? 'activated' : 'banned').' successfully'
        );
    }

    public function getChangePassword($id)
    {
        abort_if(
            Gate::denies('agent_change_password_access') || ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $agent = User::find($id);

        return view('admin.agent.change_password', compact('agent'));
    }

    public function makeChangePassword($id, Request $request)
    {
        abort_if(
            Gate::denies('agent_change_password_access') || ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $agent = User::find($id);
        $agent->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()
            ->with('success', 'Agent Change Password successfully')
            ->with('password', $request->password)
            ->with('username', $agent->user_name);
    }

    private function generateReferralCode($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public function showAgentLogin($id)
    {
        $agent = User::findOrFail($id);

        return view('auth.agent_login', compact('agent'));
    }

    public function AgentToPlayerDepositLog()
    {
        $transactions = DB::table('transactions')
            ->join('users as players', 'players.id', '=', 'transactions.payable_id')
            ->join('users as agents', 'agents.id', '=', 'players.agent_id')
            ->where('transactions.type', 'deposit')
            ->where('transactions.name', 'credit_transfer')
            ->where('agents.id', '<>', 1) // Exclude agent_id 1
            ->groupBy('agents.id', 'players.id', 'agents.name', 'players.name', 'agents.commission')
            ->select(
                'agents.id as agent_id',
                'agents.name as agent_name',
                'players.id as player_id',
                'players.name as player_name',
                'agents.commission as agent_commission', // Get the commission percentage
                DB::raw('count(transactions.id) as total_deposits'),
                DB::raw('sum(transactions.amount) as total_amount')
            )
            ->get();

        return view('admin.agent.agent_to_play_dep_log', compact('transactions'));
    }

    public function AgentToPlayerDetail($agent_id, $player_id)
    {
        // Retrieve detailed information about the agent and player
        $transactionDetails = DB::table('transactions')
            ->join('users as players', 'players.id', '=', 'transactions.payable_id')
            ->join('users as agents', 'agents.id', '=', 'players.agent_id')
            ->where('agents.id', $agent_id)
            ->where('players.id', $player_id)
            ->where('transactions.type', 'deposit')
            ->where('transactions.name', 'credit_transfer')
            ->select(
                'agents.name as agent_name',
                'players.name as player_name',
                'transactions.amount',
                'transactions.created_at',
                'agents.commission as agent_commission'
            )
            ->get();

        return view('admin.agent.agent_to_player_detail', compact('transactionDetails'));
    }

    // public function AgentWinLoseReport()
    // {
    //     $agentReports = DB::table('reports')
    // ->join('users', 'reports.agent_id', '=', 'users.id')
    // ->select(
    //     'reports.agent_id',
    //    // 'reports.agent_commission',  // Select without summing
    //     'users.name as agent_name',
    //     //'users.commission as agent_comm',

    //     DB::raw('COUNT(DISTINCT reports.id) as qty'),
    //     DB::raw('SUM(reports.bet_amount) as total_bet_amount'),
    //     DB::raw('SUM(reports.valid_bet_amount) as total_valid_bet_amount'),
    //     DB::raw('SUM(reports.payout_amount) as total_payout_amount'),
    //     DB::raw('SUM(reports.commission_amount) as total_commission_amount'),
    //     DB::raw('SUM(reports.jack_pot_amount) as total_jack_pot_amount'),
    //     DB::raw('SUM(reports.jp_bet) as total_jp_bet'),
    //     //DB::raw('SUM(reports.agent_commission) as total_agent_commission'),
    //     DB::raw('(SUM(reports.payout_amount) - SUM(reports.valid_bet_amount)) as win_or_lose'),
    //     DB::raw('COUNT(*) as stake_count'),
    //    // DB::raw('MONTHNAME(reports.created_at) as report_month_name'),  // Adding month name
    //     DB::raw('DATE_FORMAT(reports.created_at, "%Y %M") as report_month_year')  // Adding year and month name
    // )
    // ->groupBy('reports.agent_id', 'users.name', 'report_month_year')  // Grouping by year and month
    // ->get();

    // return view('admin.agent.agent_report_index', compact('agentReports'));

    // }

    public function AgentWinLoseReport(Request $request)
    {
        $query = DB::table('reports')
            ->join('users', 'reports.agent_id', '=', 'users.id')
            ->select(
                'reports.agent_id',
                'users.name as agent_name',
                DB::raw('COUNT(DISTINCT reports.id) as qty'),
                DB::raw('SUM(reports.bet_amount) as total_bet_amount'),
                DB::raw('SUM(reports.valid_bet_amount) as total_valid_bet_amount'),
                DB::raw('SUM(reports.payout_amount) as total_payout_amount'),
                DB::raw('SUM(reports.commission_amount) as total_commission_amount'),
                DB::raw('SUM(reports.jack_pot_amount) as total_jack_pot_amount'),
                DB::raw('SUM(reports.jp_bet) as total_jp_bet'),
                DB::raw('(SUM(reports.payout_amount) - SUM(reports.valid_bet_amount)) as win_or_lose'),
                DB::raw('COUNT(*) as stake_count'),
                DB::raw('DATE_FORMAT(reports.created_at, "%Y %M") as report_month_year')
            );

        // Apply the date filter if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('reports.created_at', [$request->start_date, $request->end_date]);
        } elseif ($request->has('month_year')) {
            // Filter by month and year if provided
            $monthYear = Carbon::parse($request->month_year);
            $query->whereMonth('reports.created_at', $monthYear->month)
                ->whereYear('reports.created_at', $monthYear->year);
        }

        $agentReports = $query->groupBy('reports.agent_id', 'users.name', 'report_month_year')->get();

        return view('admin.agent.agent_report_index', compact('agentReports'));
    }

    public function AgentWinLoseDetails(Request $request, $agent_id)
    {
        if ($request->ajax()) {
            $details = DB::table('reports')
                ->join('users', 'reports.agent_id', '=', 'users.id')
                ->where('reports.agent_id', $agent_id)
                ->select(
                    'reports.*',
                    'users.name as agent_name',
                    'users.commission as agent_comm',
                    DB::raw('(reports.payout_amount - reports.valid_bet_amount) as win_or_lose') // Calculating win_or_lose
                )
                ->get();

            return DataTables::of($details)
                ->make(true);

        }

        return view('admin.agent.win_lose_details');
    }

    // public function AuthAgentWinLoseReport()
    // {
    //     $agentId = Auth::user()->id;  // Get the authenticated user's agent_id
    //     //dd($agentId); auth_win_lose_details

    //     $agentReports = DB::table('reports')
    //         ->join('users', 'reports.agent_id', '=', 'users.id')
    //         ->select(
    //             'reports.agent_id',
    //             'reports.agent_commission',  // Select without summing
    //             'users.name as agent_name',
    //             'users.commission as agent_comm',
    //             DB::raw('COUNT(DISTINCT reports.id) as qty'),
    //             DB::raw('SUM(reports.bet_amount) as total_bet_amount'),
    //             DB::raw('SUM(reports.valid_bet_amount) as total_valid_bet_amount'),
    //             DB::raw('SUM(reports.payout_amount) as total_payout_amount'),
    //             DB::raw('SUM(reports.commission_amount) as total_commission_amount'),
    //             DB::raw('SUM(reports.jack_pot_amount) as total_jack_pot_amount'),
    //             DB::raw('SUM(reports.jp_bet) as total_jp_bet'),
    //             //DB::raw('SUM(reports.agent_commission) as total_agent_commission'),
    //             DB::raw('(SUM(reports.payout_amount) - SUM(reports.valid_bet_amount)) as win_or_lose'),
    //             DB::raw('COUNT(*) as stake_count'),
    //             DB::raw('DATE_FORMAT(reports.created_at, "%Y %M") as report_month_year')  // Adding year and month name
    //         )
    //         ->where('reports.agent_id', $agentId)  // Filter by authenticated user's agent_id
    //         ->groupBy('reports.agent_id', 'users.name', 'users.commission', 'reports.agent_commission', 'report_month_year')  // Grouping by year and month
    //         ->get();

    //     return view('admin.agent.auth_agent_report_index', compact('agentReports'));
    // }

    public function AuthAgentWinLoseReport(Request $request)
    {
        $agentId = Auth::user()->id;  // Get the authenticated user's agent_id

        $query = DB::table('reports')
            ->join('users', 'reports.agent_id', '=', 'users.id')
            ->select(
                'reports.agent_id',
                'users.name as agent_name',
                DB::raw('COUNT(DISTINCT reports.id) as qty'),
                DB::raw('SUM(reports.bet_amount) as total_bet_amount'),
                DB::raw('SUM(reports.valid_bet_amount) as total_valid_bet_amount'),
                DB::raw('SUM(reports.payout_amount) as total_payout_amount'),
                DB::raw('SUM(reports.commission_amount) as total_commission_amount'),
                DB::raw('SUM(reports.jack_pot_amount) as total_jack_pot_amount'),
                DB::raw('SUM(reports.jp_bet) as total_jp_bet'),
                DB::raw('(SUM(reports.payout_amount) - SUM(reports.valid_bet_amount)) as win_or_lose'),
                DB::raw('COUNT(*) as stake_count'),
                DB::raw('DATE_FORMAT(reports.created_at, "%Y %M") as report_month_year')
            )
            ->where('reports.agent_id', $agentId);  // Filter by authenticated user's agent_id

        // Apply the date filter if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('reports.created_at', [$request->start_date, $request->end_date]);
        } elseif ($request->has('month_year')) {
            // Filter by month and year if provided
            $monthYear = Carbon::parse($request->month_year);
            $query->whereMonth('reports.created_at', $monthYear->month)
                ->whereYear('reports.created_at', $monthYear->year);
        }

        $agentReports = $query->groupBy('reports.agent_id', 'users.name', 'report_month_year')->get();

        return view('admin.agent.auth_agent_report_index', compact('agentReports'));
    }

    public function AuthAgentWinLoseDetails($agent_id, $month)
    {
        $details = DB::table('reports')
            ->join('users', 'reports.agent_id', '=', 'users.id')
            ->where('reports.agent_id', $agent_id)
            ->whereMonth('reports.created_at', Carbon::parse($month)->month)
            ->whereYear('reports.created_at', Carbon::parse($month)->year)
            ->select(
                'reports.*',
                'users.name as agent_name',
                'users.commission as agent_comm',
                DB::raw('(reports.payout_amount - reports.valid_bet_amount) as win_or_lose') // Calculating win_or_lose
            )
            ->get();

        return view('admin.agent.auth_win_lose_details', compact('details'));
    }
}

/*
agent to player deposit log query
SELECT
    agents.id AS agent_id,
    agents.name AS agent_name,
    players.id AS player_id,
    players.name AS player_name,
    COUNT(transactions.id) AS total_deposits,
    SUM(transactions.amount) AS total_amount
FROM
    transactions
INNER JOIN
    users AS players ON players.id = transactions.payable_id
INNER JOIN
    users AS agents ON agents.id = players.agent_id
WHERE
    transactions.type = 'deposit'
    AND transactions.name = 'credit_transfer'
    AND agents.id <> 1 -- Exclude agent_id 1
GROUP BY
    agents.id, players.id;

    // agent report comission query
    SELECT
    agent_id,
    MONTH(created_on) as month,
    YEAR(created_on) as year,
    SUM(valid_bet_amount) as total_valid_bet_amount,
    SUM(bet_amount) as total_bet_amount,
    SUM(payout_amount) as total_payout_amount,
    SUM(commission_amount) as total_commission_amount,
    SUM(jack_pot_amount) as total_jack_pot_amount,
    SUM(jp_bet) as total_jp_bet,
    SUM(agent_commission) as total_agent_commission
FROM
    reports
GROUP BY
    agent_id,
    YEAR(created_on),
    MONTH(created_on);

    //     $agentReports = DB::table('reports')
    // ->join('users', 'reports.agent_id', '=', 'users.id')
    // ->select(
    //     'reports.agent_id',
    //     'users.name as agent_name',
    //     DB::raw('MONTH(reports.created_on) as month'),
    //     DB::raw('YEAR(reports.created_on) as year'),
    //     DB::raw('SUM(reports.bet_amount) as total_bet_amount'),
    //     DB::raw('SUM(reports.valid_bet_amount) as total_valid_bet_amount'),
    //     DB::raw('SUM(reports.payout_amount) as total_payout_amount'),
    //     DB::raw('SUM(reports.commission_amount) as total_commission_amount'),
    //     DB::raw('SUM(reports.jack_pot_amount) as total_jack_pot_amount'),
    //     DB::raw('SUM(reports.jp_bet) as total_jp_bet'),
    //     DB::raw('SUM(reports.agent_commission) as total_agent_commission')
    // )
    // ->groupBy('reports.agent_id', 'users.name', DB::raw('YEAR(reports.created_on)'), DB::raw('MONTH(reports.created_on)'))
    // ->get();
    // $agentReports = DB::table('reports')
    // ->join('users', 'reports.agent_id', '=', 'users.id')
    // ->select(
    //     'reports.agent_id',
    //     'users.name as agent_name',
    //     DB::raw('COUNT(DISTINCT reports.id) as qty'),
    //     DB::raw('SUM(reports.bet_amount) as total_bet_amount'),
    //     DB::raw('SUM(reports.valid_bet_amount) as total_valid_bet_amount'),
    //     DB::raw('SUM(reports.payout_amount) as total_payout_amount'),
    //     DB::raw('SUM(reports.commission_amount) as total_commission_amount'),
    //     DB::raw('SUM(reports.jack_pot_amount) as total_jack_pot_amount'),
    //     DB::raw('SUM(reports.jp_bet) as total_jp_bet'),
    //     DB::raw('SUM(reports.agent_commission) as total_agent_commission')
    // )
    // ->groupBy('reports.agent_id', 'users.name')
    // ->get();

    // $agentReports = DB::table('reports')
    // ->join('users', 'reports.agent_id', '=', 'users.id')
    // ->select(
    //     'reports.agent_id',
    //     'users.name as agent_name',
    //     DB::raw('COUNT(DISTINCT reports.id) as qty'),
    //     DB::raw('SUM(reports.bet_amount) as total_bet_amount'),
    //     DB::raw('SUM(reports.valid_bet_amount) as total_valid_bet_amount'),
    //     DB::raw('SUM(reports.payout_amount) as total_payout_amount'),
    //     DB::raw('SUM(reports.commission_amount) as total_commission_amount'),
    //     DB::raw('SUM(reports.jack_pot_amount) as total_jack_pot_amount'),
    //     DB::raw('SUM(reports.jp_bet) as total_jp_bet'),
    //     DB::raw('SUM(reports.agent_commission) as total_agent_commission'),
    //     DB::raw('(SUM(reports.payout_amount) - SUM(reports.valid_bet_amount)) as win_or_lose')
    // )
    // ->groupBy('reports.agent_id', 'users.name')
    // ->get();
    // return view('admin.agent.agent_report_index', compact('agentReports'));

    //     $agentReports = DB::table('reports')
    // ->join('users', 'reports.agent_id', '=', 'users.id')
    // ->select(
    //     'reports.agent_id',
    //     'users.name as agent_name',
    //     DB::raw('COUNT(DISTINCT reports.id) as qty'),
    //     DB::raw('SUM(reports.bet_amount) as total_bet_amount'),
    //     DB::raw('SUM(reports.valid_bet_amount) as total_valid_bet_amount'),
    //     DB::raw('SUM(reports.payout_amount) as total_payout_amount'),
    //     DB::raw('SUM(reports.commission_amount) as total_commission_amount'),
    //     DB::raw('SUM(reports.jack_pot_amount) as total_jack_pot_amount'),
    //     DB::raw('SUM(reports.jp_bet) as total_jp_bet'),
    //     DB::raw('SUM(reports.agent_commission) as total_agent_commission'),
    //     DB::raw('(SUM(reports.payout_amount) - SUM(reports.valid_bet_amount)) as win_or_lose'),
    //     DB::raw('COUNT(*) as stake_count')  // Adding stake count here
    // )
    // ->groupBy('reports.agent_id', 'users.name')
    // ->get();
    // return view('admin.agent.agent_report_index', compact('agentReports'));
    // $agentReports = DB::table('reports')
    // ->join('users', 'reports.agent_id', '=', 'users.id')
    // ->select(
    //     'reports.agent_id',
    //     'users.name as agent_name',
    //     DB::raw('COUNT(DISTINCT reports.id) as qty'),
    //     DB::raw('SUM(reports.bet_amount) as total_bet_amount'),
    //     DB::raw('SUM(reports.valid_bet_amount) as total_valid_bet_amount'),
    //     DB::raw('SUM(reports.payout_amount) as total_payout_amount'),
    //     DB::raw('SUM(reports.commission_amount) as total_commission_amount'),
    //     DB::raw('SUM(reports.jack_pot_amount) as total_jack_pot_amount'),
    //     DB::raw('SUM(reports.jp_bet) as total_jp_bet'),
    //     DB::raw('SUM(reports.agent_commission) as total_agent_commission'),
    //     DB::raw('(SUM(reports.payout_amount) - SUM(reports.valid_bet_amount)) as win_or_lose'),
    //     DB::raw('COUNT(*) as stake_count'),
    //     DB::raw('MONTH(reports.created_at) as report_month')  // Adding month grouping
    // )
    // ->groupBy('reports.agent_id', 'users.name', 'report_month')  // Grouping by month
    // ->get();

    // return view('admin.agent.agent_report_index', compact('agentReports'));
    //     $agentReports = DB::table('reports')
    // ->join('users', 'reports.agent_id', '=', 'users.id')
    // ->select(
    //     'reports.agent_id',
    //     'users.name as agent_name',
    //     DB::raw('COUNT(DISTINCT reports.id) as qty'),
    //     DB::raw('SUM(reports.bet_amount) as total_bet_amount'),
    //     DB::raw('SUM(reports.valid_bet_amount) as total_valid_bet_amount'),
    //     DB::raw('SUM(reports.payout_amount) as total_payout_amount'),
    //     DB::raw('SUM(reports.commission_amount) as total_commission_amount'),
    //     DB::raw('SUM(reports.jack_pot_amount) as total_jack_pot_amount'),
    //     DB::raw('SUM(reports.jp_bet) as total_jp_bet'),
    //     DB::raw('SUM(reports.agent_commission) as total_agent_commission'),
    //     DB::raw('(SUM(reports.payout_amount) - SUM(reports.valid_bet_amount)) as win_or_lose'),
    //     DB::raw('COUNT(*) as stake_count'),
    //     DB::raw('MONTHNAME(reports.created_at) as report_month_name')  // Adding month name
    // )
    // ->groupBy('reports.agent_id', 'users.name', 'report_month_name')  // Grouping by month name
    // ->get();

    // return view('admin.agent.agent_report_index', compact('agentReports'));


    }


//     public function AgentToPlayerDepositLog()
// {
//     $transactions = DB::table('transactions')
//         ->join('users as players', 'players.id', '=', 'transactions.payable_id')
//         ->join('users as agents', 'agents.id', '=', 'players.agent_id')
//         ->where('transactions.type', 'deposit')
//         ->where('transactions.name', 'credit_transfer')
//         ->where('agents.id', '<>', 1) // Exclude agent_id 1
//         ->groupBy('agents.id', 'players.id')
//         ->select(
//             'agents.id as agent_id',
//             'agents.name as agent_name',
//             'players.id as player_id',
//             'players.name as player_name',
//             DB::raw('count(transactions.id) as total_deposits'),
//             DB::raw('sum(transactions.amount) as total_amount')
//         )
//         ->get();

//     return view('admin.agent.agent_to_play_dep_log', compact('transactions'));
// }


*/
