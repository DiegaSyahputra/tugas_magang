<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CourseSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'session_date',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (CourseSession $session) {
            if (! $session->checkin_code) {
                $session->checkin_code = Str::random(10);
            }
        });
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
