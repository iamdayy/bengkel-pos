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

            // Relasi ke Pelanggan (User)
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');

            // Relasi ke Kendaraan (opsional jika pelanggan baru)
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');

            $table->dateTime('booking_date');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->timestamps();
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
