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
            DB::statement("ALTER TABLE schedules MODIFY COLUMN type ENUM('Immunization', 'Ultrasound', 'Walk-in') NOT NULL");
        } else {
            Schema::table('schedules', function (Blueprint $table) {
                $table->string('type', 30)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE schedules MODIFY COLUMN type ENUM('Immunization', 'Ultrasound') NOT NULL");
        }
    }
};
