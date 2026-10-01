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
        Schema::table('bookings', function (Blueprint $table) {
            // The drop city is a free text box on the search page, so it may name
            // a place the fleet does not run in. The label is what the customer
            // wrote, and it is kept next to the city the ride is actually served
            // from.
            $table->string('drop_city_label', 255)->nullable()->after('drop_city_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('drop_city_label');
        });
    }
};
