<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_requests', function (Blueprint $table) {
            $table->timestamp('approved_sms_at')->nullable();
        });

        Schema::table('home_service_requests', function (Blueprint $table) {
            $table->timestamp('on_the_way_sms_at')->nullable();
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->timestamp('sms_sent_at')->nullable();
            $table->unsignedInteger('sms_recipient_count')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('medicine_requests', function (Blueprint $table) {
            $table->dropColumn('approved_sms_at');
        });

        Schema::table('home_service_requests', function (Blueprint $table) {
            $table->dropColumn('on_the_way_sms_at');
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['sms_sent_at', 'sms_recipient_count']);
        });
    }
};
