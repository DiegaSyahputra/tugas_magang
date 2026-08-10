<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
        'student_code',
        'date_birth',
        'tel',
        'attendance_percent',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class);
    }

    /**
     * Hitung ulang attendance_percent & status berdasarkan data
     * attendance_records yang sebenarnya, TAPI hanya dari sesi milik
     * course yang benar-benar diikuti siswa ini (lihat tabel course_student).
     * Sesi tanpa catatan kehadiran dianggap absen (tidak dihitung "hadir"),
     * bukan diabaikan begitu saja.
     * Dipanggil otomatis lewat AttendanceRecordObserver setiap kali
     * ada catatan kehadiran baru/berubah/dihapus.
     */
    public function recalculateAttendance(): void
    {
        $enrolledCourseIds = $this->courses()->pluck('courses.id');

        $total = CourseSession::whereIn('course_id', $enrolledCourseIds)->count();

        if ($total === 0) {
            $this->update(['attendance_percent' => 0, 'status' => 'irregular']);
            return;
        }

        $attended = $this->attendanceRecords()
            ->whereIn('status', ['present', 'late'])
            ->whereHas('session', fn ($q) => $q->whereIn('course_id', $enrolledCourseIds))
            ->count();

        $percent = (int) round(($attended / $total) * 100);
        $threshold = config('attendance.regular_threshold', 70);

        $this->update([
            'attendance_percent' => $percent,
            'status' => $percent >= $threshold ? 'regular' : 'irregular',
        ]);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('student_code', 'like', "%{$term}%");
        });
    }

    public function scopeFilterStatus($query, ?string $status)
    {
        if (! $status || $status === 'all') {
            return $query;
        }

        return $query->where('status', $status);
    }
}
