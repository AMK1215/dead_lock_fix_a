<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <meta http-equiv="X-UA-Compatible" content="ie=edge">
 <title>Lucky M</title>
</head>
<body>
 <style>
    h1 {
        font-family: Arial, sans-serif;
        font-size: 24px;
        color: #333;
        margin-bottom: 20px;
        text-align: center;
        font-weight: bold;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .table th, .table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: center;
    }

    .table th {
        background-color: #f2f2f2;
        color: #333;
        font-weight: bold;
    }

    .table tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .table tbody tr:hover {
        background-color: #f1f1f1;
    }
</style>
<button><a href="{{ url('admin/auth-agent-win-lose-report')}}">Back</a></button>
<h1>Agent Detail Report for {{ $details->first()->agent_name }} ({{ \Carbon\Carbon::parse($details->first()->created_at)->format('F Y') }})</h1>


<table class="table table-bordered">
    <thead>
        <tr>
            <th>Date</th>
            <th>WagerID</th>
            <th>Bet Amount</th>
            <th>Valid Amount</th>
            <th>Payout Amount</th>
            <th>Commission Amount</th>
            <th>Jack Pot Amount</th>
            <th>JP Bet</th>
            <th>Agent Commission</th>
            <th>Win/Lose</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($details as $detail)
        <tr>
            <td>{{ $detail->created_at }}</td>
            <td>
             <a href="https://prodmd.9977997.com/Report/BetDetail?agentCode=E820&WagerID={{ $detail->wager_id }}" target="_blank" style="color: blueviolet; text-decoration: underline;">{{ $detail->wager_id }}</a>
            </td>
            <td>{{ $detail->bet_amount }}</td>
            <td>{{ $detail->valid_bet_amount }}</td>
            <td>{{ $detail->payout_amount }}</td>
            <td>{{ $detail->commission_amount }}</td>
            <td>{{ $detail->jack_pot_amount }}</td>
            <td>{{ $detail->jp_bet }}</td>
            <td>{{ $detail->agent_comm }} %</td>
            <td>{{ $detail->win_or_lose }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>