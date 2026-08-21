<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->decimal('lieu_execution_lat', 10, 7)->nullable()->after('lieu_execution');
            $table->decimal('lieu_execution_lng', 10, 7)->nullable()->after('lieu_execution_lat');
        });
    }

    public function down(): void
    {
        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->dropColumn(['lieu_execution_lat', 'lieu_execution_lng']);
        });
    }
};
