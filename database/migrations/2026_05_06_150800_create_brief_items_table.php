<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brief_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brief_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('place_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('position')->default(0);
            $table->text('ai_text')->nullable();          // texte généré par Claude
            $table->text('edited_text')->nullable();      // texte modifié par admin (override)
            $table->json('reasoning')->nullable();        // pourquoi cet item a été sélectionné
            $table->timestamps();

            $table->index(['brief_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brief_items');
    }
};
