<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Library extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'file_format',
        'file_size',
        'file_path',
        'cover_icon',
        'downloads',
        'rating',
    ];
}
