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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('purpose');
            $table->string('duration');
            $table->integer('participants');
            $table->enum('status', ['prebooking', 'in_review','pending', 'approved', 'denied'])->default('prebooking');
            $table->dateTime('booking_start');  // DateTime to store both date and time
            $table->dateTime('booking_end');    // DateTime to store both date and time
            $table->timestamps();
            $table->softDeletes();

            $table->boolean('pdfNotificationSent')->default(false);
        
            // Foreign key constraints
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('facility_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
