@extends('layouts.dashboard')

@section('title', 'Attendance')

@php($active = 'Attendance'); @endphp

@section('page')
    {{-- TOP BAR --}}
    <div class="flex items-center justify-between mb-6">
        <form method="GET" action="{{ route('attendance.index') }}" class="relative w-96">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search.."
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
            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=admin"
                class="w-9 h-9 rounded-full border border-slate-100 dark:border-slate-700 object-cover shadow-sm" alt="avatar">
        </div>
    </div>

    {{-- ALERT BANNER --}}
    @if (session('success') || session('status'))
        <div class="mb-6 bg-emerald-50/90 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-sm px-4 py-3 rounded-2xl font-medium border border-emerald-100 dark:border-emerald-500/20">
            {{ session('success') ?? session('status') ?? 'Data presensi berhasil diproses.' }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- STUDENTS TABLE (LEFT MAIN CARD) --}}
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60 flex flex-col justify-between min-h-[580px]">
            <div>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="font-bold text-lg text-slate-900 dark:text-white">Attendance</h2>
                    <div class="flex items-center gap-3">
                        <form method="GET" action="{{ route('attendance.index') }}">
                            <input type="hidden" name="q" value="{{ request('q') }}">
                            <select name="status" onchange="this.form.submit()"
                                class="text-xs font-semibold border border-slate-200 dark:border-slate-700 dark:bg-slate-700 dark:text-slate-200 rounded-xl px-3 py-2 text-slate-500 focus:outline-none">
                                <option value="all">Filter ▾</option>
                                <option value="regular" {{ request('status') === 'regular' ? 'selected' : '' }}>Regular</option>
                                <option value="irregular" {{ request('status') === 'irregular' ? 'selected' : '' }}>Irregular</option>
                            </select>
                        </form>

                        <div class="relative">
                            <button type="button"
                                onclick="document.getElementById('sessionDropdown').classList.toggle('hidden')"
                                class="bg-indigo-500 hover:bg-indigo-600 text-white text-xs font-bold rounded-xl px-4 py-2.5 shadow-sm transition">
                                + Create Now
                            </button>

                            <div id="sessionDropdown"
                                class="hidden absolute right-0 mt-2 w-72 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl shadow-xl z-20 overflow-hidden">
                                <div class="p-3 bg-slate-50 dark:bg-slate-900/60 border-b border-slate-100 dark:border-slate-700/60">
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Pilih Sesi Kelas</p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500">Pilih sesi untuk mencatat absensi</p>
                                </div>
                                <div class="max-h-60 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700/40">
                                    @forelse ($upcomingSessions as $session)
                                        <a href="{{ route('attendance.take', $session) }}"
                                            class="block px-4 py-3 text-xs hover:bg-indigo-50/50 dark:hover:bg-slate-700/50 transition-colors">
                                            <p class="font-bold text-slate-800 dark:text-slate-100">{{ $session->title }}</p>
                                            <div class="flex items-center justify-between mt-1 text-[11px] text-slate-400">
                                                <span>{{ $session->course->code ?? 'Course' }}</span>
                                                <span>{{ $session->session_date->translatedFormat('d M Y') }}</span>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="px-4 py-6 text-center text-xs text-slate-400">
                                            Belum ada sesi kelas tersedia.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DESKTOP & RESPONSIVE TABLE VIEW --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-700/60 font-semibold">
                                <th class="py-3 px-2 w-8"><input type="checkbox" class="rounded border-slate-300 text-indigo-500 focus:ring-indigo-500"></th>
                                <th class="py-3 px-3">Name</th>
                                <th class="py-3 px-3">Students ID</th>
                                <th class="py-3 px-3">Attendance</th>
                                <th class="py-3 px-3 text-center">Status</th>
                                <th class="py-3 px-2 text-center w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-700/40">
                            @forelse ($students as $student)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition-colors">
                                    <td class="py-3.5 px-2"><input type="checkbox" class="rounded border-slate-300 text-indigo-500 focus:ring-indigo-500"></td>
                                    <td class="py-3.5 px-3 font-semibold text-slate-800 dark:text-slate-100">
                                        <a href="{{ route('attendance.show', $student) }}" class="flex items-center gap-3 group">
                                            <img src="{{ $student->avatar ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($student->name) }}" 
                                                 class="w-8 h-8 rounded-full object-cover shrink-0 bg-slate-100 border border-slate-100 dark:border-slate-700" alt="">
                                            <span class="group-hover:text-indigo-600 transition-colors">{{ $student->name }}</span>
                                        </a>
                                    </td>
                                    <td class="py-3.5 px-3 font-mono text-slate-500 dark:text-slate-400">{{ $student->student_code }}</td>
                                    <td class="py-3.5 px-3 text-slate-500 dark:text-slate-400">
                                        <div class="flex items-center gap-2">
                                            <span class="w-6 h-1.5 rounded-full {{ $student->attendance_percent >= 70 ? 'bg-indigo-500' : 'bg-indigo-300' }}"></span>
                                            <span class="font-medium text-slate-400 dark:text-slate-400">{{ $student->attendance_percent }}% Response</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        @if ($student->status === 'regular')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                                ● Regular
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 dark:bg-rose-500/10 text-rose-500 dark:text-rose-400">
                                                ● Irregular
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-2 text-center text-slate-400">
                                        <div class="flex items-center justify-center gap-1">
                                            <a href="{{ route('attendance.show', $student) }}" title="Detail Presensi" class="hover:text-indigo-600 text-slate-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400 dark:text-slate-500">Belum ada data siswa.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TABLE FOOTER PAGINATION --}}
            <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-700/60 mt-4 text-xs text-slate-400">
                <span>Showing 1 to {{ count($students) }} of {{ count($students) }} results</span>
                
                <div class="flex items-center gap-1">
                    <button class="w-8 h-8 rounded-lg bg-slate-800 dark:bg-slate-700 text-white flex items-center justify-center font-bold text-xs hover:bg-slate-700 transition">&lt;</button>
                    <button class="w-8 h-8 rounded-lg bg-slate-800 dark:bg-slate-700 text-white flex items-center justify-center font-bold text-xs">1</button>
                    <button class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center font-bold text-xs hover:bg-slate-200 transition">2</button>
                    <button class="w-8 h-8 rounded-lg bg-slate-800 dark:bg-slate-700 text-white flex items-center justify-center font-bold text-xs hover:bg-slate-700 transition">&gt;</button>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN WIDGETS --}}
        <div class="space-y-6">

            {{-- ATTENDANTS HISTORY --}}
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="font-bold text-base text-slate-900 dark:text-white">Attendants History</h2>
                    <span class="text-slate-300 dark:text-slate-600 text-lg cursor-pointer">⋮</span>
                </div>
                <div class="space-y-4">
                    @forelse ($histories as $history)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <img src="{{ $history->student->avatar ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($history->student->name) }}" 
                                     class="w-9 h-9 rounded-full object-cover shrink-0" alt="">
                                <div>
                                    <p class="font-semibold text-xs text-slate-800 dark:text-slate-100">{{ $history->student->name }}</p>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">{{ $history->duration_label ?? '10 hr 30 min' }}</p>
                                </div>
                            </div>
                            
                            {{-- CIRCULAR PROGRESS RING --}}
                            <div class="relative w-9 h-9 shrink-0">
                                <svg viewBox="0 0 36 36" class="w-9 h-9 -rotate-90">
                                    <circle cx="18" cy="18" r="15" fill="none" stroke="#F1F5F9" class="dark:stroke-slate-700" stroke-width="3"></circle>
                                    <circle cx="18" cy="18" r="15" fill="none" stroke="#6366F1" stroke-width="3"
                                        stroke-dasharray="{{ round(($history->student->attendance_percent / 100) * 94.2, 2) }} 94.2"
                                        stroke-linecap="round"></circle>
                                </svg>
                                <span class="absolute inset-0 flex items-center justify-center text-[9px] font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ $history->student->attendance_percent }}%
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 dark:text-slate-500 py-4 text-center">Belum ada riwayat presensi.</p>
                    @endforelse
                </div>
            </div>

            {{-- STUDENTS ANALYTICS --}}
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="font-bold text-base text-slate-900 dark:text-white">Students Analytics</h2>
                    <span class="text-slate-300 dark:text-slate-600 text-lg cursor-pointer">⋮</span>
                </div>
                <div class="relative flex items-center justify-center" style="height: 200px;">
                    <canvas id="analyticsChart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Overall</p>
                        <p class="text-2xl font-black text-slate-800 dark:text-white mt-0.5">{{ $overallPercent }}%</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        const ctx = document.getElementById('analyticsChart');
        if (ctx) {
            new Chart(ctx, {
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
