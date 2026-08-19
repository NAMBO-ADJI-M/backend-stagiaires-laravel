<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('entreprises', 'photo_profil')) {
            Schema::table('entreprises', function (Blueprint $table) {
                $table->string('photo_profil')->nullable()->after('email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('entreprises', 'photo_profil')) {
            Schema::table('entreprises', function (Blueprint $table) {
                $table->dropColumn('photo_profil');
            });
        }
    }
};
