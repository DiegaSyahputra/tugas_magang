@extends('layouts.dashboard')

@section('title', 'Reports')

@php($active = 'Reports'); @endphp

@section('page')
    {{-- TOP BAR --}}
    <div class="flex items-center justify-between mb-6">
        <form method="GET" action="{{ route('reports.index') }}" class="relative w-96">
            <input type="text" name="q" placeholder="Search analytics & reports.."
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

    {{-- HEADER & EXPORT ACTIONS --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Reports & Academic Analytics</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Laporan performa presensi, kelulusan course, dan grafik statistik akademik.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('reports.export-pdf') }}" target="_blank"
                class="bg-rose-500 hover:bg-rose-600 text-white text-xs font-bold rounded-xl px-4 py-2.5 shadow-sm transition">
                📄 Export PDF Report
            </a>
            <a href="{{ route('reports.export-excel') }}" target="_blank"
                class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl px-4 py-2.5 shadow-sm transition">
                📊 Export Excel / CSV
            </a>
        </div>
    </div>

    {{-- 3 STAT CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/60 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-500 flex items-center justify-center text-2xl shrink-0">
                📈
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Tingkat Kehadiran</p>
                <p class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ $attendanceRate }}%</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/60 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-2xl shrink-0">
                🎓
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Course Completion</p>
                <p class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ $completionRate }}%</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/60 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-500 flex items-center justify-center text-2xl shrink-0">
                👥
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Siswa Regular vs Irregular</p>
                <p class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ $regularCount }} / {{ $irregularCount }}</p>
            </div>
        </div>
    </div>

    {{-- CHARTS GRID --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- ENROLLMENT TREND CHART --}}
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60">
            <h2 class="font-bold text-base text-slate-900 dark:text-white mb-4">Tren Pendaftaran Siswa (2026)</h2>
            <div style="height: 240px;">
                <canvas id="enrollmentTrendChart"></canvas>
            </div>
        </div>

        {{-- ATTENDANCE PER COURSE BAR CHART --}}
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60">
            <h2 class="font-bold text-base text-slate-900 dark:text-white mb-4">Rata-Rata Kehadiran Per Course</h2>
            <div style="height: 240px;">
                <canvas id="courseAttendanceChart"></canvas>
            </div>
        </div>
    </div>

    {{-- COURSE PERFORMANCE SUMMARY TABLE --}}
    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60">
        <h2 class="font-bold text-base text-slate-900 dark:text-white mb-4">Ringkasan Performa Per Kelas</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead>
                    <tr class="text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-700/60 font-semibold">
                        <th class="py-3 px-3">Kode</th>
                        <th class="py-3 px-3">Nama Course</th>
                        <th class="py-3 px-3 text-center">Siswa Terdaftar</th>
                        <th class="py-3 px-3 text-center">Jumlah Sesi</th>
                        <th class="py-3 px-3 text-center">Status Performansi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/40">
                    @forelse ($coursesSummary as $c)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="py-3 px-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $c->code }}</td>
                            <td class="py-3 px-3 font-bold text-slate-800 dark:text-slate-100">{{ $c->name }}</td>
                            <td class="py-3 px-3 text-center font-semibold text-slate-600 dark:text-slate-300">{{ $c->students_count }} Siswa</td>
                            <td class="py-3 px-3 text-center font-semibold text-slate-600 dark:text-slate-300">{{ $c->sessions_count }} Sesi</td>
                            <td class="py-3 px-3 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                    ● Performa Baik
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400">Belum ada data performa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Enrollment Line Chart
        const ctxLine = document.getElementById('enrollmentTrendChart');
        if (ctxLine) {
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
                    datasets: [{
                        label: 'Siswa Baru',
                        data: [12, 19, 25, 32, 45, 50, 62, 78],
                        borderColor: '#6366F1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // Course Bar Chart
        const ctxBar = document.getElementById('courseAttendanceChart');
        if (ctxBar) {
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: ['Web Dev', 'UI/UX', 'Mobile App', 'Data Sci', 'Backend'],
                    datasets: [{
                        label: 'Rata-Rata Presensi (%)',
                        data: [92, 85, 88, 78, 90],
                        backgroundColor: '#F59E0B',
                        borderRadius: 8
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
