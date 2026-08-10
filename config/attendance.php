<?php

return [
    // Persentase minimum kehadiran (present + late) agar siswa dianggap "Regular".
    // Di bawah nilai ini otomatis berstatus "Irregular".
    'regular_threshold' => env('ATTENDANCE_REGULAR_THRESHOLD', 70),

    // Toleransi keterlambatan (menit) sejak jam mulai sesi, dipakai oleh
    // self check-in untuk otomatis menentukan status present vs late.
    'late_tolerance_minutes' => env('ATTENDANCE_LATE_TOLERANCE_MINUTES', 15),
];
