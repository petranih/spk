<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rekap Ranking Siswa</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            width: 80px;
            height: auto;
        }
        .header h2, .header h3 {
            margin: 5px 0;
            font-weight: bold;
        }
        .header p {
            margin: 3px 0;
            font-size: 11pt;
        }
        hr {
            border: 1px solid #000;
            margin: 15px 0;
        }
        .title {
            text-align: center;
            font-weight: bold;
            margin: 15px 0;
        }
        .info {
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th {
            background-color: #f0f0f0;
            padding: 8px;
            text-align: center;
            font-weight: bold;
        }
        td {
            padding: 6px 8px;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .ttd {
            margin-top: 40px;
            text-align: right;
        }
        .ttd p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <table border="0" style="width: 100%; border: 0; margin-bottom: 10px;">
        <tr>
            <td style="width: 15%; border: 0; vertical-align: top;">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/7/74/Coat_of_arms_of_East_Java.svg/1200px-Coat_of_arms_of_East_Java.svg.png" 
                     style="width: 70px;">
            </td>
            <td style="text-align: center; border: 0;">
                <h3 style="margin: 3px 0; font-size: 14pt;">PEMERINTAH PROVINSI JAWA TIMUR</h3>
                <h3 style="margin: 3px 0; font-size: 14pt;">DINAS PENDIDIKAN</h3>
                <h2 style="margin: 3px 0; font-size: 16pt;">SEKOLAH MENENGAH ATAS NEGERI 3</h2>
                <h2 style="margin: 3px 0; font-size: 16pt;">SUMENEP</h2>
                <p style="margin: 3px 0; font-size: 11pt;">
                    Jl. Raya Lenteng Batuan - Sumenep Telp. (0328) 6771421<br>
                    E-mail: <i>sman3sumenep@gmail.com</i>
                </p>
                <h3 style="margin: 5px 0; font-size: 13pt; letter-spacing: 3px;"><u>SUMENEP</u></h3>
            </td>
            <td style="width: 15%; border: 0; text-align: right; vertical-align: top;">
                <p style="font-size: 10pt;">Kode Pos 69451</p>
            </td>
        </tr>
    </table>

    <hr>

    <!-- JUDUL -->
    <p class="title">Data Siswa Rekap Ranking</p>
    <p class="title">Periode {{ $startDate }} s/d {{ $endDate }}</p>
    <p class="info"><b>Periode : {{ $kelasName }}</b></p>

    <!-- TABEL RANKING -->
    <table>
        <thead>
            <tr>
                <th style="width: 8%;">Rank</th>
                <th style="width: 12%;">NISN</th>
                <th style="width: 22%;">Nama Siswa</th>
                <th style="width: 9%;">C1</th>
                <th style="width: 9%;">C2</th>
                <th style="width: 9%;">C3</th>
                <th style="width: 9%;">C4</th>
                <th style="width: 9%;">C5</th>
                <th style="width: 13%;">Total Skor</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dataSiswa as $index => $siswa)
            <tr>
                <td class="text-center"><b>#{{ $siswa->rank }}</b></td>
                <td class="text-center">{{ $siswa->nisn }}</td>
                <td>{{ $siswa->nama }}</td>
                <td class="text-center">{{ number_format($siswa->hadir_count, 4) }}</td>
                <td class="text-center">{{ number_format($siswa->izin_count, 4) }}</td>
                <td class="text-center">{{ number_format($siswa->sakit_count, 4) }}</td>
                <td class="text-center">{{ number_format($siswa->alpa_count, 4) }}</td>
                <td class="text-center">{{ number_format($siswa->c5_score, 4) }}</td>
                <td class="text-center"><b>{{ number_format($siswa->total_score, 4) }}</b></td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center">Tidak ada data ranking</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- TTD -->
    <div class="ttd">
        <p>Sumenep, {{ now()->format('d F Y') }}</p>
        <p><b>Kepala Sekolah SMAN 3 Sumenep</b></p>
        <br><br><br>
        <p><b>Dra. Hj. Yuliana, Spd.</b></p>
        <p>NIP. 13096342</p>
    </div>
</body>
</html>