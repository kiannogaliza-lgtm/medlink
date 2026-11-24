<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('phone_verified_at')->nullable()->after('phone_number');
            $table->boolean('registration_unverified')->default(false)->after('phone_verified_at');
            $table->string('pending_phone_number', 11)->nullable()->after('registration_unverified');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone_verified_at',
                'registration_unverified',
                'pending_phone_number',
            ]);
        });
    }
};
