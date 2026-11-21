<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name');
            $table->string('user_role');
            $table->string('phone_number')->nullable();
            $table->string('action'); // 'Account Created', 'Logged In', 'Account Updated', 'Password Changed', etc.
            $table->string('actor_name')->nullable(); // Who performed it (e.g. Self, Nurse Jane, BHW Maria)
            $table->string('actor_role')->nullable(); // 'resident', 'nurse', 'bhw', 'system'
            $table->text('details')->nullable(); // Extra details (e.g. Registered via Online Portal, Registered via Walk-in by BHW...)
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('logged_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_logs');
    }
};
