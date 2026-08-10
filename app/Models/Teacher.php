<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
        'specialization',
        'tel',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // FK teacher_id ada di tabel courses (bukan sebaliknya), dan satu guru bisa
    // mengampu banyak course, jadi relasinya hasMany dengan nama jamak.
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

}
