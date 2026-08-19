<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trajets', function (Blueprint $table) {
            $table->decimal('depart_lat', 10, 7)->nullable()->change();
            $table->decimal('depart_lng', 10, 7)->nullable()->change();
            $table->decimal('arrivee_lat', 10, 7)->nullable()->change();
            $table->decimal('arrivee_lng', 10, 7)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('trajets', function (Blueprint $table) {
            $table->decimal('depart_lat', 10, 7)->nullable(false)->change();
            $table->decimal('depart_lng', 10, 7)->nullable(false)->change();
            $table->decimal('arrivee_lat', 10, 7)->nullable(false)->change();
            $table->decimal('arrivee_lng', 10, 7)->nullable(false)->change();
        });
    }
};
