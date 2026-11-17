<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { color: #17231d; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { color: #166534; font-size: 20px; margin-bottom: 4px; }
        p { color: #52635a; margin-top: 0; }
        table { border-collapse: collapse; margin-top: 18px; width: 100%; }
        th, td { border: 1px solid #d5ded8; padding: 7px; text-align: left; }
        th { background: #eaf5ed; color: #14532d; }
        tr:nth-child(even) td { background: #f7faf8; }
    </style>
</head>
<body>
    <h1>Nature Agro — Farm Report</h1>
    <p>
        {{ \Illuminate\Support\Carbon::parse($dates['startDate'])->format('d M Y') }}
        to {{ \Illuminate\Support\Carbon::parse($dates['endDate'])->format('d M Y') }}
    </p>

    <table>
        <thead>
            <tr>
                @foreach ($rows[1] as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach (array_slice($rows, 2) as $row)
                <tr>
                    @foreach ($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
