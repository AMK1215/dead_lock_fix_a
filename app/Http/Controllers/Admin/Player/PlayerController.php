<?php

namespace App\Http\Controllers\Admin\Player;

use App\Enums\TransactionName;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlayerRequest;
use App\Http\Requests\TransferLogRequest;
use App\Models\PaymentType;
use App\Models\User;
use App\Services\UserService;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class PlayerController extends Controller
{
    protected $userService;

    private const PLAYER_ROLE = 3;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(
            Gate::denies('player_index'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );
        //kzt
        $users = User::with('roles')
            ->whereHas('roles', function ($query) {
                $query->where('role_id', self::PLAYER_ROLE);
            })
            ->where('agent_id', auth()->id())
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.player.index', compact('users'));
    }

    /**
     * Display a listing of the users with their agents.
     *
     * @return \Illuminate\View\View
     */
    public function player_with_agent()
    {
        $users = User::player()->with('roles')->get();

        return view('admin.player.list', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(
            Gate::denies('player_create'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );
        $player_name = $this->generateRandomString();
        $paymentTypes = PaymentType::all();

        return view('admin.player.create', compact('player_name', 'paymentTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PlayerRequest $request)
    {
        Gate::allows('player_create');

        $agent = Auth::user();
        $inputs = $request->validated();

        if ($this->isExistingUserForAgent($request->phone)) {
            return redirect()->back()->with('error', 'This phone number already exists');
        }

        try {
            if (isset($inputs['amount']) && $inputs['amount'] > $agent->balanceFloat) {
                throw new \Exception('Insufficient balance for transfer.');
            }

            $user = User::create([
                'name' => $inputs['name'],
                'user_name' => $inputs['user_name'],
                'password' => Hash::make($inputs['password']),
                'phone' => $inputs['phone'],
                'payment_type_id' => $inputs['payment_type_id'],
                'account_name' => $inputs['account_name'],
                'account_number' => $inputs['account_number'],
                'agent_id' => $agent->id,
                'type' => UserType::Player,
            ]);

            $user->roles()->sync(self::PLAYER_ROLE);

            if (isset($inputs['amount'])) {
                app(WalletService::class)->transfer($agent, $user, $inputs['amount'], TransactionName::CreditTransfer);
            }

            return redirect()->back()
                ->with('success', 'Player created successfully')
                ->with('url', env('APP_URL'))
                ->with('password', $request->password)
                ->with('username', $user->user_name);
        } catch (\Exception $e) {
            Log::error('Error creating user: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while creating the player.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        abort_if(
            Gate::denies('player_show'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $user_detail = User::findOrFail($id);

        return view('admin.player.show', compact('user_detail'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $player)
    {
        abort_if(
            Gate::denies('player_edit'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );
        $paymentTypes = PaymentType::all();

        return response()->view('admin.player.edit', compact('player', 'paymentTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $player)
    {

        $player->update($request->all());

        return redirect()->route('admin.player.index')->with('success', 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $player)
    {
        abort_if(
            Gate::denies('player_delete') || ! $this->ifChildOfParent(request()->user()->id, $player->id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );
        $player->delete();

        return redirect()->route('admin.player.index')->with('success', 'User deleted successfully');
    }

    public function massDestroy(Request $request)
    {
        User::whereIn('id', request('ids'))->delete();

        return response(null, 204);
    }

    public function banUser($id)
    {
        abort_if(
            ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $user = User::find($id);
        $user->update(['status' => $user->status == 1 ? 0 : 1]);

        return redirect()->back()->with(
            'success',
            'User '.($user->status == 1 ? 'activate' : 'inactive').' successfully'
        );
    }

    public function getCashIn(User $player)
    {
        abort_if(
            Gate::denies('make_transfer'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        return view('admin.player.cash_in', compact('player'));
    }

    public function makeCashIn(TransferLogRequest $request, User $player)
    {
        abort_if(
            Gate::denies('make_transfer') || ! $this->ifChildOfParent(request()->user()->id, $player->id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        try {
            $inputs = $request->validated();
            $inputs['refrence_id'] = $this->getRefrenceId();

            $agent = Auth::user();
            $cashIn = $inputs['amount'];

            if ($cashIn > $agent->balanceFloat) {

                return redirect()->back()->with('error', 'You do not have enough balance to transfer!');
            }

            app(WalletService::class)->transfer($agent, $player, $request->validated('amount'), TransactionName::CreditTransfer);

            return redirect()->back()
                ->with('success', 'CashIn submitted successfully!');
        } catch (Exception $e) {

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function getCashOut(User $player)
    {
        abort_if(
            Gate::denies('make_transfer') || ! $this->ifChildOfParent(request()->user()->id, $player->id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        return view('admin.player.cash_out', compact('player'));
    }

    public function makeCashOut(TransferLogRequest $request, User $player)
    {
        abort_if(
            Gate::denies('make_transfer') || ! $this->ifChildOfParent(request()->user()->id, $player->id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        try {
            $inputs = $request->validated();
            $inputs['refrence_id'] = $this->getRefrenceId();

            $agent = Auth::user();
            $cashOut = $inputs['amount'];

            if ($cashOut > $player->balanceFloat) {

                return redirect()->back()->with('error', 'You do not have enough balance to transfer!');
            }

            app(WalletService::class)->transfer($player, $agent, $request->validated('amount'), TransactionName::DebitTransfer);

            return redirect()->back()
                ->with('success', 'CashOut submitted successfully!');
        } catch (Exception $e) {

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function getChangePassword($id)
    {
        $player = User::find($id);

        return view('admin.player.change_password', compact('player'));
    }

    public function makeChangePassword($id, Request $request)
    {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $player = User::find($id);
        $player->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()
            ->with('success', 'Player Change Password successfully')
            ->with('password', $request->password)
            ->with('username', $player->user_name);
    }

    private function generateRandomString()
    {
        $randomNumber = mt_rand(10000000, 99999999);

        return 'LKM'.$randomNumber;
    }

    private function getRefrenceId($prefix = 'REF')
    {
        return uniqid($prefix);
    }

    public function playersByAgent(Request $request, int $agentId)
    {
        $players = User::getPlayersByAgentId($agentId);

        return view('players.index', compact('players'));
    }

    private function isExistingUserForAgent($phone): bool
    {
        //return User::where('phone', $phone)->where('agent_id', $agent_id)->first();
        return User::where('phone', $phone)->exists();
    }
}
