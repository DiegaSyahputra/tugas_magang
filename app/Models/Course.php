<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'categories',
        'name',
        'code'
    ];

    public function sessions()
    {
        return $this->hasMany(CourseSession::class);
    }

    // courses.teacher_id adalah foreign key langsung ke tabel teachers,
    // jadi relasinya belongsTo, bukan belongsToMany (tidak ada & tidak perlu tabel pivot).
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    // Dipakai di banyak tempat (AttendanceRecordController, CourseEnrollmentController,
    // SelfCheckInController, CourseSessionController, view courses/*) sebagai bentuk jamak.
    public function students()
    {
        return $this->belongsToMany(Student::class);
    }
}
