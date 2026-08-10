<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('course_session_id')->constrained('course_sessions')->cascadeOnDelete();
            $table->enum('status', ['present', 'late', 'absent'])->default('absent');
            $table->timestamp('checked_in_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable(); // lama mengikuti sesi
            $table->timestamps();

            // satu siswa hanya boleh punya 1 catatan per sesi
            $table->unique(['student_id', 'course_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
