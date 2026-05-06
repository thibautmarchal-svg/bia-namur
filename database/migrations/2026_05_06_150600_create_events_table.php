<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();

            $table->string('source', 60)->index();               // opendata / rss_delta / rss_belvedere / quefaire / manual...
            $table->string('external_id', 255)->nullable()->index();   // pour dédoublonnage
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('full_text')->nullable();

            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->nullable();
            $table->string('recurrence', 60)->nullable();

            $table->foreignId('place_id')->nullable()->constrained()->nullOnDelete();
            $table->string('venue_name')->nullable();
            $table->string('address')->nullable();

            $table->json('category')->nullable();
            $table->string('price_info')->nullable();
            $table->string('url')->nullable();
            $table->string('image_url')->nullable();

            $table->json('raw_payload')->nullable();             // données brutes de la source
            $table->timestamp('ingested_at')->nullable();
            $table->string('status', 30)->default('ingested')->index();   // ingested / normalized / selected / dropped

            $table->timestamps();

            $table->index(['city_id', 'starts_at']);
            $table->index(['city_id', 'status']);
            $table->unique(['source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
