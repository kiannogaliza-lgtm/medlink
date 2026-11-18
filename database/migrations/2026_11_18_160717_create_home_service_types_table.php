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
        Schema::create('home_service_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed initial default service types
        $defaultServices = [
            'Maternal Care',
            'Immunization',
            'Wound Care',
            'Vital Signs Monitoring',
            'Bedridden Patient Care',
            'Elderly Health Check',
            'Post-natal Care',
            'Medication Delivery',
            'Other'
        ];

        foreach ($defaultServices as $service) {
            DB::table('home_service_types')->insert([
                'name' => $service,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_service_types');
    }
};
