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
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE medicine_requests MODIFY COLUMN status ENUM('Pending', 'Accepted', 'Approved', 'Rejected') DEFAULT 'Pending'");
        } else {
            Schema::table('medicine_requests', function (Blueprint $table) {
                $table->string('status', 30)->default('Pending')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE medicine_requests SET status = 'Pending' WHERE status = 'Accepted'");
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE medicine_requests MODIFY COLUMN status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending'");
        }
    }
};
