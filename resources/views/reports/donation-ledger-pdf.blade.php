<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Donation Ledger Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .summary {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
        }
        .summary-row {
            margin: 5px 0;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Donation Ledger Report</h1>
    
    @if(isset($filters['date_from']) || isset($filters['date_to']))
    <div>
        <strong>Date Range:</strong>
        @if(isset($filters['date_from']))
            From: {{ $filters['date_from'] }}
        @endif
        @if(isset($filters['date_to']))
            To: {{ $filters['date_to'] }}
        @endif
    </div>
    @endif

    @if(isset($filters['verified']) && $filters['verified'] !== null)
    <div>
        <strong>Verified:</strong> {{ $filters['verified'] ? 'Yes' : 'No' }}
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Donor Name</th>
                <th>Amount</th>
                <th>Verified</th>
            </tr>
        </thead>
        <tbody>
            @foreach($donations as $donation)
            <tr>
                <td>{{ $donation['id'] }}</td>
                <td>{{ $donation['date'] }}</td>
                <td>{{ $donation['donor_name'] }}</td>
                <td>{{ $donation['amount'] }}</td>
                <td>{{ $donation['verified'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <h3>Summary</h3>
        <div class="summary-row"><strong>Total Amount:</strong> {{ $summary['total_amount'] }}</div>
        <div class="summary-row"><strong>Verified Amount:</strong> {{ $summary['verified_amount'] }}</div>
        <div class="summary-row"><strong>Pending Amount:</strong> {{ $summary['pending_amount'] }}</div>
    </div>
</body>
</html>

