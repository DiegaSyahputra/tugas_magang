<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $role = Auth::user()->role;
            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang kembali, ' . Auth::user()->name . ' (' . ucfirst($role) . ')!');
        }

        return back()->withErrors([
            'email' => 'Alamat email atau password yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,teacher,student'],
            'tel' => ['nullable', 'string', 'max:15'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // Auto-create Student/Teacher record if role is student/teacher
        if ($request->role === 'student') {
            $lastCodeNum = Student::count() + 10;
            Student::create([
                'name' => $user->name,
                'user_id' => $user->id,
                'student_code' => 'STU-' . str_pad($lastCodeNum, 6, '0', STR_PAD_LEFT),
                'date_birth' => now()->subYears(20)->format('Y-m-d'),
                'tel' => $request->tel ?? '08123456789',
                'status' => 'regular',
            ]);
        } elseif ($request->role === 'teacher') {
            Teacher::create([
                'name' => $user->name,
                'user_id' => $user->id,
                'specialization' => 'General Lecturer',
                'tel' => $request->tel ?? '081298765432',
            ]);
        }

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Akun berhasil dibuat! Anda telah masuk sebagai ' . ucfirst($user->role) . '.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
