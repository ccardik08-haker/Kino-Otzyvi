<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('name_key')->unique();
            $table->text('photo_url')->nullable();
            $table->uuid('file_key')->unique();
            $table->timestamps();
        });

        Schema::create('actor_movie', function (Blueprint $table) {
            $table->foreignId('actor_id')
                ->constrained('actors')
                ->cascadeOnDelete();

            $table->foreignId('movie_id')
                ->constrained('movies')
                ->cascadeOnDelete();

            $table->unsignedInteger('position')->default(0);

            $table->primary(['actor_id', 'movie_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actor_movie');
        Schema::dropIfExists('actors');
    }
};