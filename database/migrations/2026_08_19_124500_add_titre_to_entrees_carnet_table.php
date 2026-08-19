<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entrees_carnet', function (Blueprint $table) {
            $table->string('titre')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('entrees_carnet', function (Blueprint $table) {
            $table->dropColumn('titre');
        });
    }
};
