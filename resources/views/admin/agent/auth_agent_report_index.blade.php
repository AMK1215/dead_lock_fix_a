<!DOCTYPE html>
<html>
<head>
    <title>Agent Monthly Report</title>
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
  <!-- Nucleo Icons -->
  <link href="{{ asset('admin_app/assets/css/nucleo-icons.css')}}" rel="stylesheet" />
  <link href="{{ asset('admin_app/assets/css/nucleo-svg.css')}}" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/b829c5162c.js" crossorigin="anonymous"></script>
  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <!-- CSS Files -->
  {{-- <link id="pagestyle" href="{{ asset('admin_app/assets/css/material-dashboard.css?v=3.0.6')}}" rel="stylesheet" />
  <link href="{{asset('admin_app/assets/css/datatables.bootstrap.min.css')}}" rel="stylesheet"> --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .summary {
            background-color: #ffffe0;
            font-weight: bold;
        }
        .qty {
            text-align: left;
        }
        .win {
            color: green;
        }
        .lose {
            color: red;
        }
    </style>

</head>
<body>
    <h1 class="text-center">Agent Monthly Report</h1>

    <div class="card">
        <div class="card-header">
            <p class="text-center">Agent Win / lose filter by month or date</p>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.AuthAgentWinLose') }}">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}">

                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}">

                <label for="month_year">Month/Year:</label>
                <input type="month" id="month_year" name="month_year" value="{{ request('month_year') }}">

                <button type="submit">Filter</button>
            </form>

        </div>
    </div>

    <div class="mt-2">
        {{-- <div class="col-md-12"> --}}
            {{-- <div class="card mt-4"> --}}
        <table>
        <thead>
            <tr>
                <th rowspan="2">Month</th>
                <th rowspan="2">Account</th>
                <th rowspan="2">Name</th>
                <th rowspan="2">Bet Amount</th>
                <th rowspan="2">Valid Amount</th>
                <th rowspan="2">Stake Count</th>
                <th rowspan="2">Gross Comm</th>
                <th colspan="3">Member</th>
                <th colspan="3">Downline</th>
                <th colspan="3">Myself</th>
                <th colspan="3">Upline</th>
                <th colspan="3">Detail</th>
            </tr>
            <tr>
                <th>W/L</th>
                <th>Comm</th>
                <th>Total</th>
                <th>W/L</th>
                <th>Comm</th>
                <th>Total</th>
                <th>W/L</th>
                <th>Comm</th>
                <th>Total</th>
                <th>W/L</th>
                <th>Comm</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($agentReports as $report)
                <tr>
                    <td class="qty">{{ $report->report_month_year }}</td>
                    <td class="qty">Qty: {{ $report->qty }}</td>
                    <td>{{ $report->agent_name }}</td>
                    <td>{{ number_format($report->total_bet_amount, 2) }}</td>
                    <td>{{ number_format($report->total_valid_bet_amount, 2) }}</td>
                    <td>{{ $report->stake_count }}</td> <!-- Placeholder for stake count -->
                    <td>0</td>
                    
                    <!-- Win/Loss for Member -->
                    <td class="{{ $report->win_or_lose < 0 ? 'lose' : 'win' }}">
                        {{ number_format($report->win_or_lose, 2) }}
                    </td>
                    <td>0 </td>
                    <td>{{ number_format($report->win_or_lose + $report->total_commission_amount, 2) }}</td> <!-- Member Total -->
                    
                    <td>--</td> <!-- Downline W/L Placeholder -->
                    <td>0</td> <!-- Downline Comm Placeholder -->
                    <td>--</td> <!-- Downline Total Placeholder -->
                    
                    <!-- Win/Loss for Myself -->
                    <td class="{{ $report->win_or_lose < 0 ? 'lose' : 'win' }}">
                        {{ number_format($report->win_or_lose, 2) }}
                    </td>
                    <td>0</td> <!-- Myself Comm -->
                    <td>{{ number_format($report->win_or_lose + $report->total_commission_amount, 2) }}</td> <!-- Myself Total -->
                    
                    <!-- Win/Loss for Upline -->
                    <td class="{{ $report->win_or_lose < 0 ? 'lose' : 'win' }}">
                        {{ number_format($report->win_or_lose, 2) }}
                    </td>
                    <td>0</td> <!-- Upline Comm -->
                    <td>{{ number_format($report->win_or_lose + $report->total_commission_amount, 2) }}</td> <!-- Upline Total -->
                    <td>
                    <a href="{{ route('admin.authagent_winLdetails', ['agent_id' => $report->agent_id, 'month' => $report->report_month_year]) }}" class="btn btn-info">
                        View Detail
                    </a>
                    </td>
                </tr>
            @endforeach
            <tr class="summary">
                <td colspan="2">Summary:</td>
                <td>{{ number_format($agentReports->sum('total_bet_amount'), 2) }}</td>
                <td>{{ number_format($agentReports->sum('total_valid_bet_amount'), 2) }}</td>
                <td>--</td>
                <td>{{ number_format($agentReports->sum('total_commission_amount'), 2) }}</td>
                
                <!-- Summary Win/Loss -->
                <td class="{{ $agentReports->sum('win_or_lose') < 0 ? 'lose' : 'win' }}">
                    {{ number_format($agentReports->sum('win_or_lose'), 2) }}
                </td>
                <td>0</td>
                <td>{{ number_format($agentReports->sum('win_or_lose') + $agentReports->sum('total_commission_amount'), 2) }}</td>
                
                <td>--</td>
                <td>0</td>
                <td>--</td>
                
                <!-- Summary Myself Win/Loss -->
                <td class="{{ $agentReports->sum('win_or_lose') < 0 ? 'lose' : 'win' }}">
                    {{ number_format($agentReports->sum('win_or_lose'), 2) }}
                </td>
                <td>0</td>
                <td>{{ number_format($agentReports->sum('win_or_lose') + $agentReports->sum('total_commission_amount'), 2) }}</td>
                
                <!-- Summary Upline Win/Loss -->
                <td class="{{ $agentReports->sum('win_or_lose') < 0 ? 'lose' : 'win' }}">
                    {{ number_format($agentReports->sum('win_or_lose'), 2) }}
                </td>
                <td>0</td>
                <td>{{ number_format($agentReports->sum('win_or_lose') + $agentReports->sum('total_commission_amount'), 2) }}</td>
            </tr>
            </tbody>
            </table>
            {{-- </div> --}}
        {{-- </div> --}}
    </div>
</body>
</html>
