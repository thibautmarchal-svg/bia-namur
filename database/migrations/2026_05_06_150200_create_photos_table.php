<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->morphs('uploadable'); // uploadable_type + uploadable_id (Place, Story, Brief, Contribution…)
            $table->string('filename');
            $table->string('path');                  // chemin R2 ou disk local
            $table->string('disk', 30)->default('local');
            $table->string('mime_type', 60);
            $table->unsignedInteger('size');
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->json('variants')->nullable();    // {thumb: "...", card: "...", full: "..."}
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('license', 60)->nullable();
            $table->string('credit')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
