<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            // place_id ajouté dans une migration ultérieure (FK croisée stories <-> places)
            $table->unsignedBigInteger('place_id')->nullable();
            $table->string('type', 30)->default('place')->index();   // place / tradition / wallon / patrimoine
            $table->string('title');
            $table->string('slug', 200);
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->foreignId('cover_photo_id')->nullable()->constrained('photos')->nullOnDelete();
            $table->boolean('ai_generated')->default(false);
            $table->string('ai_model', 60)->nullable();
            $table->string('ai_prompt_version', 30)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('status', 30)->default('draft')->index();   // draft / pending_review / published / archived
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['city_id', 'slug']);
            $table->index(['city_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
