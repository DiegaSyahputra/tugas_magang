@extends('layouts.app')

@section('title', 'Login - SkillSet Academy')

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

        {{-- MAIN LOGIN CARD --}}
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 dark:border-slate-700/60 space-y-5">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Masuk ke Akun Anda</h2>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Masukkan kredensial login sesuai peran Anda di platform.</p>
            </div>

            @if (session('success'))
                <div class="bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-xs p-3.5 rounded-xl border border-emerald-200 dark:border-emerald-500/20 font-medium">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error') || $errors->any())
                <div class="bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 text-xs p-3.5 rounded-xl border border-rose-200 dark:border-rose-500/20 font-medium">
                    {{ session('error') ?? $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-4" autocomplete="off">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Alamat Email</label>
                    <input type="email" id="loginEmail" name="email" value="{{ old('email') }}" required autofocus
                        placeholder="nama@skillset.com" autocomplete="off"
                        class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-3 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Password</label>
                        <a href="#" onclick="alert('Silakan hubungi administrator untuk mereset password Anda.')" class="text-[11px] font-semibold text-indigo-500 hover:underline">Lupa password?</a>
                    </div>
                    <input type="password" id="loginPassword" name="password" required autocomplete="new-password"
                        placeholder="••••••••"
                        class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-3 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Ingat Saya</span>
                    </label>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white text-xs font-bold rounded-xl py-3.5 shadow-md hover:shadow-indigo-500/25 transition-all">
                    Masuk Sekarang &rarr;
                </button>
            </form>

            {{-- QUICK LOGIN ACCORDING TO USER ROLE --}}
            <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 space-y-2">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">⚡ Quick Demo Login (3 Role)</p>
                <div class="grid grid-cols-3 gap-2">
                    <button onclick="fillLogin('admin@skillset.com', 'password123')"
                        class="px-2 py-2 rounded-xl border border-indigo-200 dark:border-indigo-500/30 bg-indigo-50/50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-[11px] font-bold hover:bg-indigo-100 transition">
                        🛡️ Admin
                    </button>
                    <button onclick="fillLogin('teacher@skillset.com', 'password123')"
                        class="px-2 py-2 rounded-xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-[11px] font-bold hover:bg-emerald-100 transition">
                        👨‍🏫 Guru
                    </button>
                    <button onclick="fillLogin('student@skillset.com', 'password123')"
                        class="px-2 py-2 rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50/50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 text-[11px] font-bold hover:bg-amber-100 transition">
                        👨‍🎓 Siswa
                    </button>
                </div>
            </div>

            <div class="pt-2 text-center text-xs text-slate-400 dark:text-slate-500">
                Belum punya akun? <a href="{{ route('register') }}" class="font-bold text-indigo-500 hover:underline">Daftar sekarang</a>
            </div>
        </div>

        <p class="text-[11px] text-center text-slate-400 dark:text-slate-500">&copy; {{ date('Y') }} SkillSet Academy Platform</p>
    </div>
</div>

<script>
    function fillLogin(email, password) {
        document.getElementById('loginEmail').value = email;
        document.getElementById('loginPassword').value = password;
    }
</script>
@endsection
