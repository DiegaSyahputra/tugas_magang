<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_session_id',
        'status',           // present | late | absent
        'checked_in_at',
        'duration_minutes',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function session()
    {
        return $this->belongsTo(CourseSession::class, 'course_session_id');
    }

    public function getDurationLabelAttribute(): string
    {
        $minutes = $this->duration_minutes ?? 0;
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return "{$hours} hr {$mins} min";
    }
}
