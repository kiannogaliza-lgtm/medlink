<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE home_service_requests MODIFY COLUMN status ENUM('Pending', 'Accepted', 'Approved', 'Confirmed', 'Completed', 'Rejected') DEFAULT 'Pending'");
        } else {
            Schema::table('home_service_requests', function (Blueprint $table) {
                $table->string('status', 30)->default('Pending')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE home_service_requests SET status = 'Approved' WHERE status = 'Accepted'");
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE home_service_requests MODIFY COLUMN status ENUM('Pending', 'Approved', 'Confirmed', 'Completed', 'Rejected') DEFAULT 'Pending'");
        }
    }
};
