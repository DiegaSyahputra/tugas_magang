<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'student_id',
        'sender_name',
        'message',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
