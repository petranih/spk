// resources/views/admin/report/pdf.blade.php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ranking Beasiswa - {{ $period->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #333;
        }
        .header h2 {
            margin: 5px 0;
            color: #666;
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
        .rank-1 {
            background-color: #fff3cd;
        }
        .rank-2 {
            background-color: #d1ecf1;
        }
        .rank-3 {
            background-color: #d4edda;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>HASIL RANKING BEASISWA</h1>
        <h2>{{ $period->name }}</h2>
        <p>Periode: {{ $period->start_date->format('d/m/Y') }} - {{ $period->end_date->format('d/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>No. Aplikasi</th>
                <th>Nama Siswa</th>
                <th>Sekolah</th>
                <th>Total Skor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rankings as $ranking)
            <tr class="{{ $ranking->rank <= 3 ? 'rank-' . $ranking->rank : '' }}">
                <td>{{ $ranking->rank }}</td>
                <td>{{ $ranking->application->application_number }}</td>
                <td>{{ $ranking->application->full_name }}</td>
                <td>{{ $ranking->application->school }}</td>
                <td>{{ number_format($ranking->total_score, 6) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Sistem Beasiswa AHP</p>
    </div>
</body>
</html>
