<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trajets', function (Blueprint $table) {
            $table->decimal('current_lat', 10, 7)->nullable()->after('arrivee_lng');
            $table->decimal('current_lng', 10, 7)->nullable()->after('current_lat');
            $table->timestamp('last_position_update')->nullable()->after('current_lng');
        });
    }

    public function down(): void
    {
        Schema::table('trajets', function (Blueprint $table) {
            $table->dropColumn(['current_lat', 'current_lng', 'last_position_update']);
        });
    }
};
