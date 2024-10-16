<?php

namespace App\Http\Controllers\Admin\Bonu;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BonusController extends Controller
{
    public function index(Request $request)
    {
        $reports = $this->makeJoinTable()
            ->joinSub($this->makeAggregateQuery($request), 'aggregates', function ($join) {
                $join->on('reports.product_code', '=', 'aggregates.product_code')
                    ->on('reports.game_name', '=', 'aggregates.game_name');
            })
            ->select(
                'products.name as product_name',
                'products.code',
                'game_lists.code as game_code',
                'game_lists.name as game_list_name',
                'aggregates.total_bet_amount',
                'aggregates.total_valid_bet_amount',
                'aggregates.total_payout_amount'
            )
            ->get();

        return view('admin.bonu.index', compact('reports'));
    }

    public function show(Request $request, int $code)
    {
        $reports = $this->makeJoinTable()->select(
            'users.user_name',
            'users.id as user_id',
            'products.name as product_name',
            'products.code as product_code',
            DB::raw('SUM(reports.bet_amount) as total_bet_amount'),
            DB::raw('SUM(reports.valid_bet_amount) as total_valid_bet_amount'),
            DB::raw('SUM(reports.payout_amount) as total_payout_amount'))
            ->groupBy('users.user_name', 'product_name', 'product_code')
            ->where('reports.product_code', $code)
            ->when(isset($request->player_name), function ($query) use ($request) {
                $query->whereBetween('reports.member_name', $request->player_name);
            })
            ->when(isset($request->fromDate) && isset($request->toDate), function ($query) use ($request) {
                $query->whereBetween('reports.settlement_date', [$request->fromDate, $request->toDate]);
            })
            ->get();

        return view('admin.bonu.show', compact('reports'));
    }

    // amk
    public function detail(Request $request, int $userId, int $productCode)
    {
        $report = $this->makeJoinTable()
            ->select(
                'products.name as product_name',
                'users.user_name',
                'users.id as user_id',
                'reports.wager_id',
                'reports.valid_bet_amount',
                'reports.bet_amount',
                'reports.payout_amount',
                'reports.settlement_date',
                'game_lists.code as game_code',
                'game_lists.name as game_list_name'
            )
            ->where('users.id', $userId)
            ->where('reports.product_code', $productCode)
            ->when($request->has('fromDate') && $request->has('toDate'), function ($query) use ($request) {
                $query->whereBetween('reports.settlement_date', [$request->fromDate, $request->toDate]);
            })
            ->get();

        $player = User::find($userId);

        return view('admin.bonu.detail', compact('report', 'player'));
    }

    private function makeJoinTable()
    {
        $query = User::query()->roleLimited();
        $query->join('reports', 'reports.member_name', '=', 'users.user_name')
            ->join('products', 'reports.product_code', '=', 'products.code')
            ->join('game_lists', 'reports.game_name', '=', 'game_lists.code')
            ->where('reports.status', '101');

        return $query;
    }

    private function makeAggregateQuery(Request $request)
    {
        return DB::table('reports')
            ->select(
                'product_code',
                'game_name',
                DB::raw('SUM(bet_amount) as total_bet_amount'),
                DB::raw('SUM(valid_bet_amount) as total_valid_bet_amount'),
                DB::raw('SUM(payout_amount) as total_payout_amount')
            )
            ->when(isset($request->fromDate) && isset($request->toDate), function ($query) use ($request) {
                $query->whereBetween('settlement_date', [$request->fromDate, $request->toDate]);
            })
            ->groupBy('product_code', 'game_name');
    }
}
