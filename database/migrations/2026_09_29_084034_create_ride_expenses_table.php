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
        Schema::create('ride_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            // The driver who paid for the expense. Nullable so the rows survive
            // a driver being removed from the system.
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category');
            $table->decimal('amount', 10, 2);
            $table->string('bill_number')->nullable();
            // Photo of the bill, the admin checks the amount against it.
            $table->string('bill_photo');
            $table->text('notes')->nullable();
            $table->date('spent_on');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ride_expenses');
    }
};
