<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 200);
            $table->string('name');
            $table->string('type', 50)->index();              // cafe / restaurant / bar / library / patrimoine / marche / parc / hidden_gem...
            $table->string('description', 500)->nullable();
            $table->foreignId('story_id')->nullable()->constrained('stories')->nullOnDelete();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('address')->nullable();
            $table->string('neighborhood', 80)->nullable()->index();

            $table->json('opening_hours')->nullable();
            $table->json('contact')->nullable();              // {phone, email, website, instagram}
            $table->json('tags')->nullable();                 // ['terrasse', 'matin', 'famille', 'bio']

            $table->foreignId('cover_photo_id')->nullable()->constrained('photos')->nullOnDelete();

            $table->string('source', 30)->default('admin')->index();   // admin / opendata / contribution
            $table->string('status', 30)->default('draft')->index();   // draft / published / archived

            // Préparation freemium / encarts éditorialisés (cf. brief §15.3)
            $table->boolean('is_sponsored')->default(false);
            $table->string('sponsored_label')->nullable();
            $table->timestamp('sponsored_until')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['city_id', 'slug']);
            $table->index(['city_id', 'status']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
