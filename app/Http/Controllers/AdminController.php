<?php

namespace App\Http\Controllers;

use App\Models\OtpAttempt;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class AdminController extends Controller
{
    public function login()
    {
        return view('admin.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate(['password' => 'required']);

        if (! hash_equals((string) env('ADMIN_PASSWORD', 'admin'), (string) $request->password)) {
            return back()->with('error', 'Wrong password.');
        }

        $request->session()->regenerate();
        $request->session()->put('is_admin', true);

        return redirect()->route('admin.index');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('is_admin');
        return redirect()->route('admin.login');
    }

    public function index()
    {
        return view('admin.index', [
            'settings' => [
                'smtp_host'         => Setting::get('smtp_host'),
                'smtp_port'         => Setting::get('smtp_port', 587),
                'smtp_username'     => Setting::get('smtp_username'),
                'smtp_encryption'   => Setting::get('smtp_encryption', 'tls'),
                'smtp_from_address' => Setting::get('smtp_from_address'),
                'smtp_from_name'    => Setting::get('smtp_from_name', 'OTP Demo'),
                'has_password'      => (bool) Setting::get('smtp_password'),
            ],
            'board'    => OtpAttempt::leaderboard()->limit(50)->get(),
            'started'  => OtpAttempt::count(),
            'finished' => OtpAttempt::whereNotNull('finished_ms')->count(),
        ]);
    }

    public function saveSmtp(Request $request)
    {
        $data = $request->validate([
            'smtp_host'         => 'required|string|max:190',
            'smtp_port'         => 'required|integer|between:1,65535',
            'smtp_username'     => 'nullable|string|max:190',
            'smtp_password'     => 'nullable|string|max:190',
            'smtp_encryption'   => 'required|in:tls,ssl,none',
            'smtp_from_address' => 'nullable|email|max:190',
            'smtp_from_name'    => 'nullable|string|max:190',
        ]);

        foreach ($data as $key => $value) {
            if ($key === 'smtp_password') {
                if ($value !== null && $value !== '') {
                    Setting::put($key, Crypt::encryptString($value));   // blank = keep the saved one
                }
                continue;
            }
            Setting::put($key, $value);
        }

        return back()->with('success', 'SMTP settings saved.');
    }

    public function testSmtp(Request $request)
    {
        $data = $request->validate(['to' => 'required|email']);

        try {
            OtpController::deliver($data['to'], 'SMTP test', 'If you can read this, your SMTP settings work.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Test failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Test email sent to ' . $data['to'] . '.');
    }

    public function clearBoard()
    {
        OtpAttempt::query()->delete();
        return back()->with('success', 'Leaderboard cleared.');
    }
}
