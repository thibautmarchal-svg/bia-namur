<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->json('bounding_box')->nullable();
            $table->string('primary_color', 9)->default('#C77F2C');
            $table->foreignId('founder_admin_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
