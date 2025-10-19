<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .header {
            background-color: #667eea;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: white;
            padding: 30px;
            border: 1px solid #ddd;
        }
        .otp-box {
            background-color: #f0f0f0;
            border: 2px solid #667eea;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            border-radius: 5px;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            letter-spacing: 5px;
        }
        .footer {
            background-color: #f9f9f9;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px;
            border-radius: 3px;
            margin: 20px 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Sistem Beasiswa SMA 3 SUMENEP</h2>
        </div>
        
        <div class="content">
            <p>Halo {{ $user->name }},</p>
            
            <p>Kami menerima permintaan untuk mereset password akun Anda. Gunakan kode OTP di bawah untuk melanjutkan proses reset password.</p>
            
            <div class="otp-box">
                <p style="margin: 0; font-size: 14px; color: #666;">Kode OTP Anda:</p>
                <div class="otp-code">{{ $otp }}</div>
            </div>
            
            <div class="warning">
                <strong>⚠️ Penting:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Kode OTP ini berlaku selama 15 menit</li>
                    <li>Jangan bagikan kode ini kepada siapapun</li>
                    <li>Jika Anda tidak meminta reset password, abaikan email ini</li>
                </ul>
            </div>
            
            <p>Jika Anda mengalami masalah, hubungi administrator sistem.</p>
            
            <p>Salam,<br>
            <strong>Tim Sistem Beasiswa SMA 3 SUMENEP</strong></p>
        </div>
        
        <div class="footer">
            <p>&copy; 2024 SMA 3 SUMENEP. Semua hak dilindungi.</p>
        </div>
    </div>
</body>
</html>