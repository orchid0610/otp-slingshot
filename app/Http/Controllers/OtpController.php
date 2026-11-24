<?php

namespace App\Http\Controllers;

use App\Models\OtpAttempt;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{
    public const CODE_LENGTH = 6;

    /** Count a throw that never reaches the code row as a mistake. */
    public const COUNT_MISSES = true;

    public function index()
    {
        return view('home', ['board' => $this->board()]);
    }

    public function leaderboard()
    {
        return view('partials.leaderboard', ['board' => $this->board()]);
    }

    private function board()
    {
        return OtpAttempt::leaderboard()->limit(10)->get();
    }

    public function send(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:190']);

        $code = str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);

        $attempt = OtpAttempt::create([
            'email'      => $data['email'],
            'code'       => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        $request->session()->put('attempt_id', $attempt->id);

        return redirect()->route('verify.show');
    }

    public function show(Request $request)
    {
        $attempt = $this->current($request);
        if (! $attempt) {
            return redirect()->route('home')->with('error', 'Please request a new code.');
        }

        return view('verify', [
            'attempt' => $attempt,
            'length'  => self::CODE_LENGTH,
            'email'   => $attempt->email,
            'board'   => $this->board(),
        ]);
    }

    /** One slingshot throw. Server is the source of truth for the code, progress and mistakes. */
    public function throwDigit(Request $request)
    {
        $attempt = $this->current($request);
        if (! $attempt || $attempt->finished_ms) {
            return response()->json(['message' => 'Session expired'], 410);
        }

        $data = $request->validate([
            'digit'  => 'required|integer|between:0,9',
            'result' => 'required|in:active,wrong_box,miss',
        ]);

        $now = (int) round(microtime(true) * 1000);
        $attempt->started_ms ??= $now;

        $ok = false;
        if ($data['result'] === 'active') {
            $ok = (int) $attempt->code[$attempt->progress] === (int) $data['digit'];
            $ok ? $attempt->progress++ : $attempt->mistakes++;
        } elseif ($data['result'] === 'wrong_box' || self::COUNT_MISSES) {
            $attempt->mistakes++;
        }

        $done = $attempt->progress >= self::CODE_LENGTH;
        if ($done) {
            $attempt->finished_ms = $now;
        }
        $attempt->save();

        $payload = [
            'ok'       => $ok,
            'progress' => $attempt->progress,
            'mistakes' => $attempt->mistakes,
            'started'  => $attempt->started_ms,
            'done'     => $done,
        ];

        if ($done) {
            $duration = $attempt->finished_ms - $attempt->started_ms;
            $better = OtpAttempt::whereNotNull('finished_ms')->where('id', '!=', $attempt->id)
                ->where(function ($q) use ($attempt, $duration) {
                    $q->where('mistakes', '<', $attempt->mistakes)
                      ->orWhere(function ($q) use ($attempt, $duration) {
                          $q->where('mistakes', $attempt->mistakes)
                            ->whereRaw('(finished_ms - started_ms) < ?', [$duration]);
                      });
                })->count();

            $payload['duration'] = $duration;
            $payload['rank'] = $better + 1;
            $request->session()->forget('attempt_id');
        }

        return response()->json($payload);
    }

    private function current(Request $request): ?OtpAttempt
    {
        $attempt = OtpAttempt::find($request->session()->get('attempt_id'));
        return $attempt && $attempt->expires_at->isFuture() ? $attempt : null;
    }

    /** Sends through the SMTP saved in /admin, or falls back to the log when nothing is configured. */
    public static function deliver(string $to, string $subject, string $body): void
    {
        $host = Setting::get('smtp_host');

        if (! $host) {
            Log::info("[OTP demo - SMTP not configured] to={$to} | {$body}");
            return;
        }

        $enc = Setting::get('smtp_encryption', 'tls');
        $password = Setting::get('smtp_password');

        config(['mail.mailers.demo' => [
            'transport'  => 'smtp',
            'host'       => $host,
            'port'       => (int) Setting::get('smtp_port', 587),
            'username'   => Setting::get('smtp_username'),
            'password'   => $password ? Crypt::decryptString($password) : null,
            'scheme'     => $enc === 'ssl' ? 'smtps' : 'smtp',
            'encryption' => $enc === 'none' ? null : $enc,
            'timeout'    => 15,
        ]]);

        $from = Setting::get('smtp_from_address') ?: Setting::get('smtp_username');
        $name = Setting::get('smtp_from_name', 'OTP Demo');

        Mail::mailer('demo')->raw($body, function ($m) use ($to, $subject, $from, $name) {
            $m->to($to)->subject($subject)->from($from, $name);
        });
    }
}
