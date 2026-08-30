<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conventions', function (Blueprint $table) {
            $table->uuid('autorisation_pointage_id')->nullable()->after('id');
            $table->uuid('carnet_id')->nullable()->change();

            $table->foreign('autorisation_pointage_id')->references('id')->on('autorisations_pointage')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('conventions', function (Blueprint $table) {
            $table->dropForeign(['autorisation_pointage_id']);
            $table->dropColumn('autorisation_pointage_id');
            $table->uuid('carnet_id')->nullable(false)->change();
        });
    }
};
