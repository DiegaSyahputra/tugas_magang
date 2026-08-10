<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libraries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category');
            $table->string('file_format');
            $table->string('file_size');
            $table->string('file_path')->nullable();
            $table->string('cover_icon')->default('📚');
            $table->integer('downloads')->default(0);
            $table->decimal('rating', 3, 1)->default(5.0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libraries');
    }
};
