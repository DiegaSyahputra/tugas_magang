@extends('layouts.dashboard')

@section('title', "Detail Course - {$course->name}")

@php($active = 'Courses'); @endphp

@section('page')
    <div class="space-y-6">
        {{-- BACK LINK & HERO HEADER --}}
        <div>
            <a href="{{ route('courses.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline mb-3">
                <span>&larr; Kembali ke Master Courses</span>
            </a>

            @if (session('success'))
                <div class="mb-4 flex items-center justify-between p-4 text-sm rounded-2xl border bg-emerald-50/80 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-200/60 dark:border-emerald-500/20 transition-all">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">✅</span>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">&times;</button>
                </div>
            @endif

            {{-- COURSE HERO CARD --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200/40">
                            {{ $course->categories ?? 'Mata Pelajaran' }}
                        </span>
                        <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                            {{ $course->code }}
                        </span>
                    </div>
                    
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white mt-1.5">{{ $course->name }}</h1>
                    
                    <div class="flex flex-wrap items-center gap-3 mt-2 text-xs text-slate-500 dark:text-slate-400">
                        <span class="flex items-center gap-1.5 font-semibold text-slate-700 dark:text-slate-200">
                            👥 {{ $course->students->count() }} Siswa Terdaftar
                        </span>
                        <span>&middot;</span>
                        <span class="flex items-center gap-1.5">
                            👨‍🏫 @if ($course->teacher)
                                <span>Diampu oleh <strong class="text-slate-800 dark:text-slate-100">{{ $course->teacher->name }}</strong></span>
                            @else
                                <span class="text-amber-500 font-semibold">⚠️ Belum ada pengajar</span>
                            @endif
                        </span>
                    </div>
                </div>

                {{-- FORM DAFTARKAN SISWA BARU --}}
                <form method="POST" action="{{ route('courses.enroll', $course) }}" class="bg-slate-50 dark:bg-slate-900/60 p-4 rounded-xl border border-slate-100 dark:border-slate-700/60 flex flex-col gap-2 shrink-0 md:w-80">
                    @csrf
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Daftarkan Siswa ke Kelas</label>
                    <select name="student_id[]" multiple size="3"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 dark:text-slate-100 rounded-lg px-2.5 py-1.5 text-xs focus:outline-none focus:border-indigo-500">
                        @forelse ($availableStudents as $student)
                            <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->student_code }})</option>
                        @empty
                            <option disabled>Semua siswa sudah terdaftar</option>
                        @endforelse
                    </select>
                    <div class="flex items-center justify-between text-[10px] text-slate-400">
                        <span>Tahan Ctrl/Cmd untuk multi-select</span>
                        <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg px-3 py-1.5 shadow-sm transition">
                            + Daftarkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- DAFTAR SESI PERTEMUAN --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700/60 space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    <h2 class="font-extrabold text-sm text-slate-800 dark:text-white uppercase tracking-wider">Sesi Pertemuan Kelas</h2>
                </div>
                <a href="{{ route('courses.sessions.create', $course) }}"
                    class="inline-flex items-center gap-1 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white text-xs font-bold rounded-xl px-3.5 py-2 shadow-sm transition-all">
                    <span>+ Tambah Sesi</span>
                </a>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-700/60">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-400 dark:text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-100 dark:border-slate-700">
                        <tr>
                            <th class="py-3 px-4">Judul Pertemuan</th>
                            <th class="py-3 px-4">Tanggal</th>
                            <th class="py-3 px-4">Waktu</th>
                            <th class="py-3 px-4 text-right">Aksi Cepat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        @forelse ($course->sessions as $session)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition-colors">
                                <td class="py-3 px-4 font-bold text-slate-800 dark:text-slate-100">
                                    {{ $session->title }}
                                </td>
                                <td class="py-3 px-4 text-slate-500 dark:text-slate-400 font-medium">
                                    {{ $session->session_date->translatedFormat('d M Y') }}
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-500 dark:text-slate-400">
                                    {{ substr($session->start_time, 0, 5) }} - {{ substr($session->end_time, 0, 5) }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('attendance.take', $session) }}"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 transition">
                                            Ambil Absensi
                                        </a>
                                        <a href="{{ route('courses.sessions.checkin-info', [$course, $session]) }}"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 transition">
                                            QR Check-in
                                        </a>
                                        <a href="{{ route('courses.sessions.edit', [$course, $session]) }}"
                                            class="px-2 py-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                            Edit
                                        </a>
                                        <form action="{{ route('courses.sessions.destroy', [$course, $session]) }}" method="POST"
                                            class="inline" onsubmit="return confirm('Hapus sesi ini beserta data absensinya?')">
                                            @csrf @method('DELETE')
                                            <button class="px-2 py-1 text-rose-500 dark:text-rose-400 hover:underline">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                    Belum ada sesi pertemuan untuk kelas ini. Klik "+ Tambah Sesi" di atas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- DAFTAR SISWA TERDAFTAR --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-sm border border-slate-100 dark:border-slate-700/60 space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <h2 class="font-extrabold text-sm text-slate-800 dark:text-white uppercase tracking-wider">Siswa Terdaftar ({{ $course->students->count() }})</h2>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-700/60">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-400 dark:text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-100 dark:border-slate-700">
                        <tr>
                            <th class="py-3 px-4">Nama Siswa</th>
                            <th class="py-3 px-4">Student ID</th>
                            <th class="py-3 px-4 text-center">Persentase Presensi</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        @forelse ($course->students as $student)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition-colors">
                                <td class="py-3 px-4 font-bold text-slate-800 dark:text-slate-100">
                                    <div class="flex items-center gap-2.5">
                                        <img src="{{ $student->avatar ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($student->name) }}"
                                             class="w-7 h-7 rounded-full object-cover shrink-0" alt="">
                                        <span>{{ $student->name }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-500 dark:text-slate-400">
                                    {{ $student->student_code }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold {{ $student->attendance_percent >= 70 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">
                                    {{ $student->attendance_percent }}%
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <form action="{{ route('courses.students.destroy', [$course, $student]) }}" method="POST"
                                        onsubmit="return confirm('Keluarkan {{ $student->name }} dari kelas ini?')">
                                        @csrf @method('DELETE')
                                        <button class="px-2.5 py-1 text-rose-500 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-lg text-xs font-semibold transition">
                                            Keluarkan
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                    Belum ada siswa terdaftar di kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
