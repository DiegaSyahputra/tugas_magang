@extends('layouts.dashboard')

@section('title', 'Home Dashboard')

@php($active = 'Home'); @endphp

@section('page')
    {{-- TOP BAR --}}
    <div class="flex items-center justify-between mb-6">
        <form method="GET" action="{{ route('dashboard') }}" class="relative w-96">
            <input type="text" name="q" placeholder="Search courses, students, sessions.."
                class="w-full bg-white dark:bg-slate-800 dark:text-slate-100 rounded-full pl-10 pr-4 py-2.5 text-sm border border-slate-100 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-200/50 shadow-sm">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">🔍</span>
        </form>

        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs font-semibold bg-rose-50 dark:bg-rose-500/10 text-rose-500 px-3 py-1.5 rounded-full border border-rose-100 dark:border-rose-500/20">
                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span> Live
            </span>
            <button onclick="toggleDarkMode()" title="Ganti mode terang/gelap"
                class="w-9 h-9 rounded-full bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 shadow-sm hover:bg-slate-50">
                <span class="dark:hidden">🌙</span>
                <span class="hidden dark:inline">☀️</span>
            </button>
            <button class="w-9 h-9 rounded-full bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 flex items-center justify-center text-slate-400 shadow-sm hover:bg-slate-50">🔔</button>
            <img src="{{ 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode(auth()->user()->name ?? 'User') }}"
                class="w-9 h-9 rounded-full border border-slate-100 dark:border-slate-700 object-cover shadow-sm" alt="avatar"
                title="{{ auth()->user()->name ?? 'User' }} ({{ ucfirst(auth()->user()->role ?? 'guest') }})">
        </div>
    </div>

    {{-- WELCOME HERO BANNER --}}
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-500 rounded-3xl p-6 sm:p-8 text-white shadow-lg shadow-indigo-500/15 mb-6 relative overflow-hidden">
        <div class="relative z-10 max-w-xl">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/20 backdrop-blur-md text-white mb-3 inline-block">
                🎓 SkillSet Academy Platform &middot; Peran: {{ ucfirst(auth()->user()->role ?? 'User') }}
            </span>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight leading-tight">Selamat Datang Kembali, {{ auth()->user()->name ?? 'User' }}!</h1>
            <p class="text-indigo-100 text-xs sm:text-sm mt-2 leading-relaxed">
                Kelola presensi siswa, jadwal live class, katalog course, dan transaksi keuangan dalam satu dashboard terpadu yang modern.
            </p>
        </div>
        <div class="absolute right-0 bottom-0 top-0 opacity-10 pointer-events-none text-9xl font-black flex items-center">
            SkillSet
        </div>
    </div>

    {{-- 4 STAT CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/60 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-500 flex items-center justify-center text-2xl shrink-0">
                👨‍🎓
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total Siswa</p>
                <p class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ $totalStudents }} Siswa</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/60 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-2xl shrink-0">
                📘
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Course Aktif</p>
                <p class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ $totalCourses }} Kelas</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/60 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-500 flex items-center justify-center text-2xl shrink-0">
                👨‍🏫
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Staf Pengajar</p>
                <p class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ $totalTeachers }} Guru</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/60 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-500/10 text-rose-500 flex items-center justify-center text-2xl shrink-0">
                ⭐
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Rata-Rata Presensi</p>
                <p class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ $overallPercent }}%</p>
            </div>
        </div>
    </div>

    {{-- MAIN GRID: RECENT COURSES & ANALYTICS --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- LEFT MAIN CARD --}}
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60 space-y-5">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-base text-slate-900 dark:text-white">Katalog Course Terbaru</h2>
                <a href="{{ route('courses.index') }}" class="text-xs font-bold text-indigo-500 hover:underline">Lihat Semua &rarr;</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @forelse ($recentCourses as $course)
                    <div class="border border-slate-100 dark:border-slate-700/60 rounded-2xl p-4 hover:border-indigo-200 dark:hover:border-indigo-500/30 transition-all bg-slate-50/50 dark:bg-slate-700/30">
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                                {{ $course->code }}
                            </span>
                            <span class="text-[11px] font-bold text-slate-400">👥 {{ $course->students_count }} Siswa</span>
                        </div>
                        <p class="font-bold text-xs text-slate-800 dark:text-slate-100 line-clamp-1">{{ $course->name }}</p>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Kategori: {{ $course->categories ?? 'General' }}</p>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 py-6 text-center col-span-2">Belum ada course tersedia.</p>
                @endforelse
            </div>
        </div>

        {{-- RIGHT WIDGET: STUDENTS ANALYTICS --}}
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60">
            <div class="flex items-center justify-between mb-5">
                <h2 class="font-bold text-base text-slate-900 dark:text-white">Students Analytics</h2>
                <span class="text-slate-300 dark:text-slate-600 text-lg cursor-pointer">⋮</span>
            </div>
            <div class="relative flex items-center justify-center" style="height: 200px;">
                <canvas id="homeAnalyticsChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Overall</p>
                    <p class="text-2xl font-black text-slate-800 dark:text-white mt-0.5">{{ $overallPercent }}%</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctxHome = document.getElementById('homeAnalyticsChart');
        if (ctxHome) {
            new Chart(ctxHome, {
                type: 'doughnut',
                data: {
                    labels: ['Regular', 'Irregular'],
                    datasets: [{
                        data: [{{ $overallPercent }}, {{ max(0, 100 - $overallPercent) }}],
                        backgroundColor: ['#F59E0B', '#6366F1'],
                        borderWidth: 0,
                        cutout: '76%'
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    </script>
@endsection
