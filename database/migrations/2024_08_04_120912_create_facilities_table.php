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
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('facility_name');
            $table->string('facility_type');
            $table->integer('capacity');
            $table->string('building_name');
            $table->integer('floor_level');
            $table->string('room_number');
            $table->text('description');
            $table->string('facility_image')->nullable();
            $table->boolean('status')->default(true); // active/inactive
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
