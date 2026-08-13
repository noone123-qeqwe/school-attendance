<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #222; }
        h2 { font-size: 16px; margin-bottom: 4px; }
        .subtitle { font-size: 10px; color: #666; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #7f432e; color: white; padding: 8px 10px; text-align: left; font-size: 10px; }
        td { padding: 7px 10px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) td { background: #fdf6ec; }
    </style>
</head>
<body>
    <h2>Admins List</h2>
    <div class="subtitle">Generated on {{ now()->format('F j, Y g:i A') }}</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Employee ID</th>
                <th>Email</th>
                <th>Department</th>
                <th>Position</th>
            </tr>
        </thead>
        <tbody>
            @foreach($Admins as $i => $Admin)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $Admin->name }}</td>
                <td>{{ $Admin->employee_id ?? '—' }}</td>
                <td>{{ $Admin->email }}</td>
                <td>{{ $Admin->department ?? '—' }}</td>
                <td>{{ $Admin->position ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
