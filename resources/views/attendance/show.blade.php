@extends('layouts.dashboard')

@section('title', "Detail Presensi - {$student->name}")

@php($active = 'Attendance'); @endphp

@section('page')
    <div class="space-y-6">
        {{-- BACK LINK & HEADER --}}
        <div>
            <a href="{{ route('attendance.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline mb-3">
                <span>&larr; Kembali ke Dashboard Presensi</span>
            </a>
            
            {{-- STUDENT HERO CARD --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <img src="{{ $student->avatar ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($student->name) }}" 
                         class="w-16 h-16 rounded-full object-cover border-2 border-indigo-500/30 p-0.5 shrink-0 bg-slate-100" alt="{{ $student->name }}">
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $student->name }}</h1>
                            @if ($student->status === 'regular')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200/50">Regular</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-200/50">Irregular</span>
                            @endif
                        </div>
                        <p class="text-xs font-mono text-indigo-600 dark:text-indigo-400 font-semibold mt-0.5">{{ $student->student_code }}</p>
                        
                        <div class="flex flex-wrap gap-1 mt-2">
                            @foreach ($student->courses as $course)
                                <span class="text-[10px] font-semibold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded-md">
                                    {{ $course->code }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="sm:text-right border-t sm:border-t-0 pt-3 sm:pt-0 border-slate-100 dark:border-slate-700/60">
                    <p class="text-xs font-medium text-slate-400 dark:text-slate-400">Tingkat Kehadiran Overall</p>
                    <p class="text-3xl font-black {{ $student->attendance_percent >= 70 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500 dark:text-rose-400' }} mt-0.5">
                        {{ $student->attendance_percent }}%
                    </p>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Akumulasi per sesi terdaftar</p>
                </div>
            </div>
        </div>

        {{-- STATS CARDS GRID --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/60 text-center">
                <p class="text-2xl font-black text-slate-800 dark:text-white">{{ $total }}</p>
                <p class="text-xs font-medium text-slate-400 dark:text-slate-400 mt-1">Total Sesi</p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/60 text-center">
                <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $present }}</p>
                <p class="text-xs font-medium text-slate-400 dark:text-slate-400 mt-1">Hadir Tepat Waktu</p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/60 text-center">
                <p class="text-2xl font-black text-amber-500 dark:text-amber-400">{{ $late }}</p>
                <p class="text-xs font-medium text-slate-400 dark:text-slate-400 mt-1">Terlambat</p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/60 text-center">
                <p class="text-2xl font-black text-rose-500 dark:text-rose-400">{{ $absent }}</p>
                <p class="text-xs font-medium text-slate-400 dark:text-slate-400 mt-1">Absen / Tidak Hadir</p>
            </div>
        </div>

        {{-- GRID TREN & TIMELINE --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- WEEKLY TREND CHART (1 COL) --}}
            <div class="lg:col-span-1 bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    <h2 class="font-extrabold text-sm text-slate-800 dark:text-white uppercase tracking-wider">Tren (8 Minggu Terakhir)</h2>
                </div>
                <div class="relative h-60">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            {{-- SESSION TIMELINE TABLE (2 COLS) --}}
            <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <h2 class="font-extrabold text-sm text-slate-800 dark:text-white uppercase tracking-wider">Riwayat Kehadiran per Sesi</h2>
                    </div>
                    <span class="text-xs text-slate-400">{{ count($records) }} Rekaman</span>
                </div>

                <div class="max-h-[380px] overflow-y-auto rounded-xl border border-slate-100 dark:border-slate-700/60">
                    <table class="w-full text-xs text-left">
                        <thead class="sticky top-0 bg-slate-50 dark:bg-slate-900 text-slate-400 dark:text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-100 dark:border-slate-700">
                            <tr>
                                <th class="py-3 px-4">Tanggal</th>
                                <th class="py-3 px-4">Sesi Pertemuan</th>
                                <th class="py-3 px-4">Course</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Durasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            @forelse ($records as $record)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition-colors">
                                    <td class="py-3 px-4 text-slate-500 dark:text-slate-400 font-medium">
                                        {{ $record->session->session_date->translatedFormat('d M Y') }}
                                    </td>
                                    <td class="py-3 px-4 font-bold text-slate-800 dark:text-slate-100">
                                        {{ $record->session->title }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-mono px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                            {{ $record->session->course->code }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        @if ($record->status === 'present')
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200/50">
                                                Hadir
                                            </span>
                                        @elseif ($record->status === 'late')
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-200/50">
                                                Telat
                                            </span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-200/50">
                                                Absen
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right text-slate-400 dark:text-slate-500 font-medium">
                                        {{ $record->duration_minutes ? $record->duration_label : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                        Belum ada riwayat kehadiran tercatat untuk siswa ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script>
        const trendLabels = @json($weeklyTrend->pluck('label'));
        const trendData = @json($weeklyTrend->pluck('percent'));

        const trendCtx = document.getElementById('trendChart');
        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: '% Kehadiran',
                        data: trendData,
                        borderColor: '#6366F1',
                        backgroundColor: 'rgba(99,102,241,0.12)',
                        fill: true,
                        tension: 0.3,
                        spanGaps: true,
                        pointBackgroundColor: '#4F46E5',
                        pointRadius: 4,
                    }]
                },
                options: {
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            min: 0,
                            max: 100,
                            ticks: { callback: (v) => v + '%' }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                }
            });
        }
    </script>
@endsection
