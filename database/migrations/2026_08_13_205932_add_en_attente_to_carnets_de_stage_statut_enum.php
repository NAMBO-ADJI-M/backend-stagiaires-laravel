<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE carnets_de_stage MODIFY COLUMN statut ENUM('EN_ATTENTE', 'EN_COURS', 'ARCHIVE') NOT NULL DEFAULT 'EN_ATTENTE'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE carnets_de_stage MODIFY COLUMN statut ENUM('EN_COURS', 'ARCHIVE') NOT NULL DEFAULT 'EN_COURS'");
    }
};