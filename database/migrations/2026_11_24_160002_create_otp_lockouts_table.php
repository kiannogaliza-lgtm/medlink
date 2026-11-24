<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_lockouts', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 11);
            $table->string('purpose', 20);
            $table->timestamp('locked_until');
            $table->timestamps();

            $table->unique(['phone', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_lockouts');
    }
};
