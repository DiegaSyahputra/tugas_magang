<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterStudent;
use App\Models\AttendanceRecord;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Student::with('user');

        if ($request->filled('q')) {
            $query->search($request->q);
        }

        if ($request->filled('status')) {
            $query->filterStatus($request->status);
        }

        $students = $query->latest()->get();

        $histories = AttendanceRecord::with(['student', 'session'])
            ->whereIn('status', ['present', 'late'])
            ->whereNotNull('checked_in_at')
            ->latest('checked_in_at')
            ->take(5)
            ->get();

        $totalStudents = Student::count();
        $regularCount = Student::where('status', 'regular')->count();
        $irregularCount = Student::where('status', 'irregular')->count();
        $overallPercent = round(Student::avg('attendance_percent') ?? 0, 1);

        return view('students.index', compact(
            'students',
            'histories',
            'overallPercent',
            'totalStudents',
            'regularCount',
            'irregularCount'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('students.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MasterStudent $request)
    {
        $request->merge([
            'email'        => strtolower(trim($request->email)),
            'name'         => trim($request->name),
            'tel'          => trim($request->tel),
        ]);

        try {

            DB::transaction(function () use ($request) {

                $code = $this->generateStudentCode();

                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'role' => 'student',
                    'password' => Hash::make('password123'),
                ]);

                Student::create([
                    'user_id' => $user->id,
                    'name' => $request->name,
                    'student_code' => $code,
                    'date_birth' => $request->date_birth,
                    'tel' => $request->tel,
                    'attendance_percent' => 0,
                    'status' => $request->status,
                ]);
            });
            return redirect()->route('students.index')->with([
                'status' => 'success',
                'message' => 'Data Berhasil Di Tambahkan'
            ]);

        } catch (\Exception $e) {
            Log::error('Gagal tambah Data', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withInput()->with([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menambahkan data: '
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {

        $student = Student::findOrFail($id);
        $title = 'Edit Data';
        return view('students.edit', compact('student', 'title'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MasterStudent $request, $id)
    {
        // Trim/sanitize input sebelum diproses
        $request->merge([
            'name'         => trim($request->name),
            'tel'          => trim($request->tel),
        ]);

        try {

            DB::transaction(function () use ($request, $id) {

                $student = Student::findOrFail($id);
                $user    = $student->user; // Mendapatkan relasi ke tabel User (jika ada)

                // 1. Update data Student
                $student->update([
                    'name'               => $request->name,
                    'date_birth'         => $request->date_birth,
                    'tel'                => $request->tel,
                    'status'             => $request->status,
                ]);

                // 2. Sync/Update data User jika relasi user_id terhubung
                if ($user) {
                    $userData = [
                        'name' => $request->name,
                    ];

                    // Opsional: Jika di form update ada field email untuk user
                    if ($request->filled('email')) {
                        $userData['email'] = trim($request->email);
                    }

                    // Opsional: Jika di form update ada field password baru
                    if ($request->filled('new_password')) {
                        $userData['password'] = Hash::make($request->new_password);
                    }

                    $user->update($userData);
                }
            });

            return redirect()->route('students.index')->with([
                'status'  => 'success',
                'message' => 'Data Siswa Berhasil Diperbarui'
            ]);

        } catch (\Exception $e) {
            Log::error('Gagal Memperbarui Siswa', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withInput()->with([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $student = Student::findOrFail($id);
            $student->delete();

            return redirect()->route('students.index')->with([
                'status' => 'success',
                'message' => 'Data Berhasil Dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Gagal Menghapus Siswa', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withInput()->with([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage()
            ]);
        }
    }

    private function generateStudentCode()
    {
        $code = 'STU-';

        $lastCode = Student::where('student_code', 'like', $code . '%')
            ->orderBy('student_code', 'desc')
            ->first();

        if ($lastCode) {
            $lastNumber = (int)substr($lastCode->student_code, strlen($code));
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }

        return $code . $newNumber;
    }
}
