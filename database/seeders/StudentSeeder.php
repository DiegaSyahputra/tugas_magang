<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $students = [
            ['name' => 'Jacob Jones',   'student_code' => 'STU-000010', 'attendance_percent' => 40, 'status' => 'irregular', 'date_birth' => '2002-05-12', 'tel' => '08123456789'],
            ['name' => 'Jane Cooper',   'student_code' => 'STU-000012', 'attendance_percent' => 30, 'status' => 'irregular', 'date_birth' => '2001-08-20', 'tel' => '08123456788'],
            ['name' => 'Cody Fisher',   'student_code' => 'STU-000014', 'attendance_percent' => 80, 'status' => 'regular', 'date_birth' => '2003-01-15', 'tel' => '08123456787'],
            ['name' => 'Jenny Wilson',  'student_code' => 'STU-000016', 'attendance_percent' => 30, 'status' => 'irregular', 'date_birth' => '2002-11-04', 'tel' => '08123456786'],
            ['name' => 'Jerome Bell',   'student_code' => 'STU-000018', 'attendance_percent' => 60, 'status' => 'irregular', 'date_birth' => '2000-09-30', 'tel' => '08123456785'],
            ['name' => 'Albert Flores', 'student_code' => 'STU-000020', 'attendance_percent' => 40, 'status' => 'regular', 'date_birth' => '2002-03-25', 'tel' => '08123456784'],
            ['name' => 'Floyd Miles',   'student_code' => 'STU-000022', 'attendance_percent' => 80, 'status' => 'irregular', 'date_birth' => '2001-07-19', 'tel' => '08123456783'],
            ['name' => 'Arlene McCoy',  'student_code' => 'STU-000024', 'attendance_percent' => 30, 'status' => 'regular', 'date_birth' => '2003-04-18', 'tel' => '08123456782'],
            ['name' => 'Guy Hawkins',   'student_code' => 'STU-000026', 'attendance_percent' => 90, 'status' => 'regular', 'date_birth' => '2002-06-14', 'tel' => '08123456781'],
            ['name' => 'Devon Lane',    'student_code' => 'STU-000028', 'attendance_percent' => 65, 'status' => 'regular', 'date_birth' => '2001-12-01', 'tel' => '08123456780'],
        ];

        foreach ($students as $student) {
            Student::updateOrCreate(
                ['student_code' => $student['student_code']],
                [
                    'name' => $student['name'],
                    'attendance_percent' => $student['attendance_percent'],
                    'status' => $student['status'],
                    'date_birth' => $student['date_birth'],
                    'tel' => $student['tel'],
                ]
            );
        }
    }
}
