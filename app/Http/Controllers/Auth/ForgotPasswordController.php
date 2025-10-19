<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    /**
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\View\View
     */
    public function showForgotForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Send OTP to user email
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'Email tidak terdaftar dalam sistem.',
        ]);

        $user = User::where('email', $request->email)->first();

        // Generate OTP 6 digit
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Simpan OTP ke database dengan waktu expired 15 menit
        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'otp' => $otp,
                'token' => Str::random(60),
                'otp_expires_at' => now()->addMinutes(15),
                'created_at' => now(),
            ]
        );

        // Coba kirim email
        try {
            Mail::send('emails.send-otp', ['otp' => $otp, 'user' => $user], function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Kode OTP Reset Password - Sistem Beasiswa');
            });
        } catch (\Exception $e) {
            \Log::error('Email sending error: ' . $e->getMessage());
        }

        // Redirect ke halaman verifikasi OTP dengan pesan
        return redirect()->route('password.verify-otp')
                        ->with('email', $request->email)
                        ->with('status', 'Kode OTP telah dikirim ke email Anda. Berlaku selama 15 menit.')
                        ->with('otp_for_testing', $otp); // Untuk development saja
    }

    /**
     * Display the form to verify OTP
     *
     * @return \Illuminate\View\View
     */
    public function showVerifyOtpForm(Request $request)
    {
        $email = $request->query('email') ?? session('email');
        $otp_for_testing = session('otp_for_testing'); // Untuk development
        
        return view('auth.passwords.verify-otp', [
            'email' => $email,
            'otp_for_testing' => $otp_for_testing
        ]);
    }

    /**
     * Verify OTP code
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric|digits:6',
        ]);

        $passwordReset = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$passwordReset) {
            return back()->withErrors(['otp' => 'Kode OTP tidak valid.'])
                        ->withInput();
        }

        // Cek apakah OTP masih berlaku
        if (now()->isAfter($passwordReset->otp_expires_at)) {
            DB::table('password_resets')->where('email', $request->email)->delete();
            return back()->withErrors(['otp' => 'Kode OTP telah kadaluarsa. Silakan minta OTP baru.'])
                        ->withInput();
        }

        return redirect()->route('password.reset', $passwordReset->token)
                        ->with('email', $request->email)
                        ->with('status', 'OTP berhasil diverifikasi. Silakan buat password baru.');
    }

    /**
     * Display the password reset view for the given token.
     *
     * @param  string  $token
     * @return \Illuminate\View\View
     */
    public function showResetForm($token)
    {
        $passwordReset = DB::table('password_resets')
            ->where('token', $token)
            ->first();

        if (!$passwordReset) {
            return redirect()->route('password.request')
                            ->withErrors(['token' => 'Token tidak valid.']);
        }

        return view('auth.passwords.reset', [
            'token' => $token, 
            'email' => $passwordReset->email
        ]);
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
public function resetPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
        'password' => 'required|string|min:8|confirmed',
        'token' => 'required',
    ]);

    $passwordReset = DB::table('password_resets')
        ->where('email', $request->email)
        ->where('token', $request->token)
        ->first();

    if (!$passwordReset) {
        return back()->withErrors(['email' => 'Token atau email tidak valid.']);
    }

    // Update password user
    User::where('email', $request->email)->update([
        'password' => bcrypt($request->password),
    ]);

    // Hapus record dari password_resets
    DB::table('password_resets')->where('email', $request->email)->delete();

    return redirect()->route('login')
                    ->with('success', 'Password berhasil direset. Silakan login dengan password baru.');
}
}