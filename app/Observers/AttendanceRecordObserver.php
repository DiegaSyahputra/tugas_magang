<?php

namespace App\Observers;

use App\Models\AttendanceRecord;

class AttendanceRecordObserver
{
    public function saved(AttendanceRecord $record): void
    {
        $record->student?->recalculateAttendance();
    }

    public function deleted(AttendanceRecord $record): void
    {
        $record->student?->recalculateAttendance();
    }
}
