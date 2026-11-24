<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpAttempt extends Model
{
    protected $guarded = [];
    protected $casts = ['expires_at' => 'datetime'];

    public function getDurationMsAttribute(): ?int
    {
        return $this->finished_ms && $this->started_ms ? $this->finished_ms - $this->started_ms : null;
    }

    /** Public-safe display name, e.g. j***@gmail.com */
    public function getMaskedEmailAttribute(): string
    {
        [$local, $domain] = array_pad(explode('@', $this->email, 2), 2, '');
        return mb_substr($local, 0, 1) . '***@' . $domain;
    }

    /** Finished attempts ordered best first: fewest mistakes, then fastest. */
    public function scopeLeaderboard($q)
    {
        return $q->whereNotNull('finished_ms')
            ->orderBy('mistakes')
            ->orderByRaw('(finished_ms - started_ms)');
    }
}
