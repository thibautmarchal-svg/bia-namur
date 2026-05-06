<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la FK stories.place_id -> places.id après la création de la table places
     * (FK croisée places.story_id <-> stories.place_id résolue par migrations séparées).
     */
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->foreign('place_id')
                ->references('id')->on('places')
                ->nullOnDelete();
            $table->index('place_id');
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropForeign(['place_id']);
            $table->dropIndex(['place_id']);
        });
    }
};
