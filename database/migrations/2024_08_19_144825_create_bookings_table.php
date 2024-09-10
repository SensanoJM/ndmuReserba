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
            //$table->string('requester'); // This might be redundant if user_id is used
            $table->string('purpose');
            $table->string('duration');
            $table->integer('participants');
            $table->date('booking_date');
            $table->json('booking_attachments')->nullable();
            $table->json('equipment')->nullable();
            $table->string('policy')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'canceled'])->default('pending'); // Add status for approval process
            $table->timestamps();
            $table->time('start_time');
            $table->time('end_time');
        
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
