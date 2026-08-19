<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->uuidMorphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('entreprises') && !Schema::hasColumn('entreprises', 'photo_profil')) {
            Schema::table('entreprises', function (Blueprint $table) {
                $table->string('photo_profil')->nullable()->after('email');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        if (Schema::hasTable('entreprises') && Schema::hasColumn('entreprises', 'photo_profil')) {
            Schema::table('entreprises', function (Blueprint $table) {
                $table->dropColumn('photo_profil');
            });
        }
    }
};
