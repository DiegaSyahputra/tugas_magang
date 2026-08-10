<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'course_session_id',
        'title',
        'room_code',
        'scheduled_at',
        'duration_minutes',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Meeting $meeting) {
            if (! $meeting->room_code) {
                $meeting->room_code = 'skillset-' . Str::random(10);
            }
        });
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function session()
    {
        return $this->belongsTo(CourseSession::class, 'course_session_id');
    }

    public function messages()
    {
        return $this->hasMany(MeetingMessage::class)->latest();
    }

    // Peserta = siswa yang terdaftar di course terkait meeting ini
    public function participants()
    {
        return $this->course->students()->orderBy('name')->get();
    }

    public function getStatusAttribute(): string
    {
        if ($this->ended_at) {
            return 'ended';
        }

        if ($this->started_at) {
            return 'live';
        }

        return 'scheduled';
    }

    // URL video call, embed Jitsi Meet (gratis, tanpa API key)
    public function getVideoUrlAttribute(): string
    {
        return "https://meet.jit.si/{$this->room_code}";
    }
}
