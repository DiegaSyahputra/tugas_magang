<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterTeacher;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Teacher::with(['user', 'courses'])->withCount('courses');

        if ($request->filled('q')) {
            $term = $request->q;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('specialization', 'like', "%{$term}%")
                  ->orWhereHas('user', function ($uq) use ($term) {
                      $uq->where('email', 'like', "%{$term}%");
                  });
            });
        }

        $teachers = $query->latest()->get();

        $totalTeachers = Teacher::count();
        $totalCoursesTaught = \App\Models\Course::whereNotNull('teacher_id')->count();
        $specializationCount = Teacher::whereNotNull('specialization')
            ->where('specialization', '!=', '')
            ->distinct('specialization')
            ->count('specialization');

        return view('teachers.index', compact(
            'teachers',
            'totalTeachers',
            'totalCoursesTaught',
            'specializationCount'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('teachers.create');

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MasterTeacher $request)
    {
        $request->merge([
            'email'         => strtolower(trim($request->email)),
            'name'          => trim($request->name),
            'tel'           => trim($request->tel),
            'specialization' => trim($request->specialization),
        ]);

        try {

            DB::transaction(function () use ($request) {

                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'role' => 'teacher',
                    'password' => Hash::make('password123'),
                ]);

                Teacher::create([
                    'user_id' => $user->id,
                    'name' => $request->name,
                    'tel' => $request->tel,
                    'specialization' => $request->specialization,
                ]);
            });
            return redirect()->route('teachers.index')->with([
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
    public function show(Teacher $teacher)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $teacher = Teacher::findOrFail($id);
        $title = 'Edit Data';
        return view('teachers.edit', compact('teacher', 'title'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MasterTeacher $request, $id)
    {
        // Trim/sanitize input sebelum diproses
        $request->merge([
            'name'         => trim($request->name),
            'specialization' => trim($request->specialization),
            'tel'          => trim($request->tel),
        ]);

        try {

            DB::transaction(function () use ($request, $id) {

                $teacher = Teacher::findOrFail($id);
                $user    = $teacher->user; // Mendapatkan relasi ke tabel User (jika ada)

                // 1. Update data teacher
                $teacher->update([
                    'name'               => $request->name,
                    'specialization'     => $request->specialization,
                    'tel'                => $request->tel,
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

            return redirect()->route('teachers.index')->with([
                'status'  => 'success',
                'message' => 'Data Guru Berhasil Diperbarui'
            ]);

        } catch (\Exception $e) {
            Log::error('Gagal Memperbarui', [
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
            $teacher = Teacher::findOrFail($id);
            DB::transaction(function () use ($teacher) {
                $user = $teacher->user;
                $teacher->delete();
                if ($user) {
                    $user->delete();
                }
            });

            return redirect()->route('teachers.index')->with([
                'status' => 'success',
                'message' => 'Data Berhasil Dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Gagal Menghapus', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withInput()->with([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat menghapus data guru.'
            ]);
        }
    }
}
