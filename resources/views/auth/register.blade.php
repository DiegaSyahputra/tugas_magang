@extends('layouts.app')

@section('title', 'Daftar Akun Baru - SkillSet Academy')

@section('content')
<div class="min-h-screen flex items-center justify-center p-4 bg-slate-50 dark:bg-slate-900 transition-colors">
    <div class="w-full max-w-md space-y-4">
        
        {{-- BRANDING HEADER --}}
        <div class="flex items-center justify-between px-2">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white font-extrabold flex items-center justify-center shadow-md shadow-indigo-500/20 text-lg">
                    S
                </div>
                <div>
                    <h1 class="font-extrabold text-base text-slate-900 dark:text-white tracking-tight">SkillSet Academy</h1>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">Platform Online Course & Learning</p>
                </div>
            </div>

            <button onclick="toggleDarkMode()" title="Ganti mode terang/gelap"
                class="w-9 h-9 rounded-xl bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 flex items-center justify-center text-sm shadow-sm text-slate-600 dark:text-slate-300">
                <span class="dark:hidden">🌙</span>
                <span class="hidden dark:inline">☀️</span>
            </button>
        </div>

        {{-- MAIN REGISTER CARD --}}
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 dark:border-slate-700/60 space-y-5">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Buat Akun Baru</h2>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Pilih peran akun Anda dan isi formulir pendaftaran.</p>
            </div>

            @if ($errors->any())
                <div class="bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 text-xs p-3.5 rounded-xl border border-rose-200 dark:border-rose-500/20 font-medium">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        placeholder="Misal: Jacob Jones"
                        class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Alamat Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        placeholder="nama@gmail.com"
                        class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Pilih Peran Akun (Role)</label>
                    <select name="role" required
                        class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                        <option value="student" {{ old('role') === 'student' ? 'selected' : '' }}>👨‍🎓 Siswa (Student)</option>
                        <option value="teacher" {{ old('role') === 'teacher' ? 'selected' : '' }}>👨‍🏫 Guru / Pengajar (Teacher)</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>🛡️ Administrator System</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Password</label>
                        <input type="password" name="password" required placeholder="Minimal 8 karakter"
                            class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" required placeholder="Ulangi password"
                            class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white text-xs font-bold rounded-xl py-3.5 shadow-md hover:shadow-indigo-500/25 transition-all mt-2">
                    Daftar Akun Sekarang &rarr;
                </button>
            </form>

            <div class="pt-2 text-center text-xs text-slate-400 dark:text-slate-500">
                Sudah memiliki akun? <a href="{{ route('login') }}" class="font-bold text-indigo-500 hover:underline">Masuk ke sini</a>
            </div>
        </div>

        <p class="text-[11px] text-center text-slate-400 dark:text-slate-500">&copy; {{ date('Y') }} SkillSet Academy Platform</p>
    </div>
</div>
@endsection
