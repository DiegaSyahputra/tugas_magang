@extends('layouts.app')

@section('title', 'Check-in - ' . $session->title)

@section('content')
<div class="min-h-screen flex items-center justify-center p-4 bg-slate-50 dark:bg-slate-900 transition-colors">
    <div class="w-full max-w-md space-y-4">
        
        {{-- BRANDING & TOGGLE HEADER --}}
        <div class="flex items-center justify-between px-2">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white font-extrabold flex items-center justify-center shadow-md shadow-indigo-500/20 text-lg">
                    S
                </div>
                <div>
                    <h1 class="font-extrabold text-base text-slate-900 dark:text-white tracking-tight">SkillSet Check-in</h1>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">Presensi Mandiri Sesi Kelas</p>
                </div>
            </div>

            <button onclick="toggleDarkMode()" title="Ganti mode terang/gelap"
                class="w-9 h-9 rounded-xl bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 flex items-center justify-center text-sm shadow-sm text-slate-600 dark:text-slate-300">
                <span class="dark:hidden">🌙</span>
                <span class="hidden dark:inline">☀️</span>
            </button>
        </div>

        {{-- MAIN CHECK-IN CARD --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60">

            @if (session('checkedIn'))
                @php $checked = session('checkedIn'); @endphp
                <div class="text-center py-6 space-y-3">
                    @if ($checked['status'] === 'present')
                        <div class="w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-3xl mx-auto border border-emerald-200 dark:border-emerald-500/20 animate-bounce">
                            ✅
                        </div>
                        <h2 class="font-extrabold text-lg text-emerald-600 dark:text-emerald-400">Terima kasih, {{ $checked['name'] }}!</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Kehadiran Anda telah berhasil tercatat <strong class="text-emerald-600 dark:text-emerald-400">Tepat Waktu</strong>.</p>
                    @else
                        <div class="w-16 h-16 rounded-full bg-amber-50 dark:bg-amber-500/10 text-amber-500 flex items-center justify-center text-3xl mx-auto border border-amber-200 dark:border-amber-500/20">
                            ⏰
                        </div>
                        <h2 class="font-extrabold text-lg text-amber-600 dark:text-amber-400">Terima kasih, {{ $checked['name'] }}!</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Kehadiran Anda telah tercatat, namun dikategorikan <strong class="text-amber-500 dark:text-amber-400">Terlambat</strong>.</p>
                    @endif
                    <p class="text-[11px] font-mono text-slate-400 dark:text-slate-500 pt-2 border-t border-slate-100 dark:border-slate-700/60">
                        {{ now()->translatedFormat('d F Y, H:i') }} WIB
                    </p>
                </div>
            @else
                <div class="mb-5 pb-4 border-b border-slate-100 dark:border-slate-700/60">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200/40">
                        {{ $session->course->name }}
                    </span>
                    <h2 class="font-black text-lg text-slate-900 dark:text-white mt-1.5">{{ $session->title }}</h2>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 flex items-center gap-2">
                        <span>📅 {{ $session->session_date->translatedFormat('d M Y') }}</span>
                        <span>&middot;</span>
                        <span>⏰ {{ $session->start_time }} - {{ $session->end_time }}</span>
                    </p>
                </div>

                @if ($errors->any())
                    <div class="mb-4 bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 text-xs px-4 py-3 rounded-xl border border-rose-200 dark:border-rose-500/20">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('checkin.store', $session->checkin_code) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Pilih Nama Siswa</label>
                        <select name="student_id" required 
                            class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-3 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                            <option value="">-- pilih nama siswa --</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->student_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" 
                        class="w-full bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white text-xs font-extrabold rounded-xl py-3.5 shadow-md hover:shadow-indigo-500/25 transition-all active:scale-95">
                        ✋ Saya Hadir Sekarang
                    </button>
                </form>

                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700/60 text-center">
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">
                        💡 Presensi setelah {{ config('attendance.late_tolerance_minutes', 15) }} menit dari jam mulai akan otomatis berstatus <strong>"Telat"</strong>.
                    </p>
                </div>
            @endif
        </div>

        <p class="text-[11px] text-center text-slate-400 dark:text-slate-500">&copy; {{ date('Y') }} SkillSet Academy Platform</p>
    </div>
</div>
@endsection
