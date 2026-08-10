@extends('layouts.dashboard')

@section('title', 'Ambil Absensi Sesi Kelas')

@php($active = 'Attendance'); @endphp

@section('page')
    <div class="max-w-3xl mx-auto space-y-6">
        <div>
            <a href="{{ route('attendance.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline mb-3">
                <span>&larr; Kembali ke Dashboard Presensi</span>
            </a>

            {{-- SESSION INFO HEADER --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200/40">
                        {{ $session->course->name ?? 'Course' }}
                    </span>
                    <h1 class="text-xl font-extrabold text-slate-900 dark:text-white mt-1.5">{{ $session->title }}</h1>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 flex items-center gap-2">
                        <span>📅 {{ $session->session_date->translatedFormat('d M Y') }}</span>
                        <span>&middot;</span>
                        <span>⏰ {{ $session->start_time }} - {{ $session->end_time }}</span>
                    </p>
                </div>

                <div class="shrink-0 bg-slate-50 dark:bg-slate-900/60 p-3 rounded-xl border border-slate-100 dark:border-slate-700 text-center">
                    <p class="text-xs text-slate-400 dark:text-slate-500">Jumlah Peserta</p>
                    <p class="text-lg font-black text-indigo-600 dark:text-indigo-400">{{ count($students) }} Siswa</p>
                </div>
            </div>
        </div>

        {{-- ATTENDANCE FORM CARD --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60">
            <h2 class="font-bold text-sm text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                <span>📝</span>
                <span>Pencatatan Kehadiran Siswa</span>
            </h2>

            <form method="POST" action="{{ route('attendance.take.store', $session) }}" class="space-y-6">
                @csrf

                <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-700/60">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-400 dark:text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-100 dark:border-slate-700">
                            <tr>
                                <th class="py-3 px-4 w-10 text-center">#</th>
                                <th class="py-3 px-4">Nama Siswa</th>
                                <th class="py-3 px-4">Student ID</th>
                                <th class="py-3 px-4 text-center">Status Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            @foreach ($students as $index => $student)
                                @php $current = $existing[$student->id] ?? 'present'; @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition-colors">
                                    <td class="py-3 px-4 text-center font-mono text-slate-400">{{ $index + 1 }}</td>
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
                                    <td class="py-3 px-4">
                                        <div class="flex items-center justify-center gap-3">
                                            {{-- Present Option --}}
                                            <label class="flex items-center gap-1.5 cursor-pointer px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 has-[:checked]:bg-emerald-50 has-[:checked]:border-emerald-300 dark:has-[:checked]:bg-emerald-500/10 dark:has-[:checked]:border-emerald-500/30 transition-all">
                                                <input type="radio" name="attendance[{{ $student->id }}]" value="present"
                                                    {{ $current === 'present' ? 'checked' : '' }} class="accent-emerald-600">
                                                <span class="font-bold text-emerald-600 dark:text-emerald-400">Hadir</span>
                                            </label>

                                            {{-- Late Option --}}
                                            <label class="flex items-center gap-1.5 cursor-pointer px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-amber-50 dark:hover:bg-amber-500/10 has-[:checked]:bg-amber-50 has-[:checked]:border-amber-300 dark:has-[:checked]:bg-amber-500/10 dark:has-[:checked]:border-amber-500/30 transition-all">
                                                <input type="radio" name="attendance[{{ $student->id }}]" value="late"
                                                    {{ $current === 'late' ? 'checked' : '' }} class="accent-amber-500">
                                                <span class="font-bold text-amber-500 dark:text-amber-400">Telat</span>
                                            </label>

                                            {{-- Absent Option --}}
                                            <label class="flex items-center gap-1.5 cursor-pointer px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-rose-50 dark:hover:bg-rose-500/10 has-[:checked]:bg-rose-50 has-[:checked]:border-rose-300 dark:has-[:checked]:bg-rose-500/10 dark:has-[:checked]:border-rose-500/30 transition-all">
                                                <input type="radio" name="attendance[{{ $student->id }}]" value="absent"
                                                    {{ $current === 'absent' ? 'checked' : '' }} class="accent-rose-500">
                                                <span class="font-bold text-rose-500 dark:text-rose-400">Absen</span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('attendance.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                        Batal
                    </a>
                    <button type="submit"
                        class="bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white text-xs font-semibold px-6 py-2.5 rounded-xl shadow-sm hover:shadow-indigo-500/25 transition-all">
                        Simpan Absensi
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
