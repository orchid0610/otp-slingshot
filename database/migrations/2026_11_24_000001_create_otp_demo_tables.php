<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
        });

        Schema::create('otp_attempts', function (Blueprint $t) {
            $t->id();
            $t->string('email')->index();
            $t->string('code', 6);
            $t->unsignedTinyInteger('progress')->default(0);   // digits placed correctly so far
            $t->unsignedSmallInteger('mistakes')->default(0);
            $t->unsignedBigInteger('started_ms')->nullable();  // first throw
            $t->unsignedBigInteger('finished_ms')->nullable();
            $t->timestamp('expires_at');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_attempts');
        Schema::dropIfExists('settings');
    }
};
