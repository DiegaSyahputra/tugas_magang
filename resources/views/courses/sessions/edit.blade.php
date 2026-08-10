@extends('layouts.dashboard')

@section('title', 'Edit Sesi - ' . $course->name)

@php($active = 'Courses'); @endphp

@section('page')
    <div class="max-w-xl mx-auto space-y-6">
        <div>
            <a href="{{ route('courses.show', $course) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline mb-3">
                <span>&larr; Kembali ke {{ $course->name }}</span>
            </a>

            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-lg shrink-0">
                        ✏️
                    </div>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Edit Sesi Pertemuan</h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $course->name }} ({{ $course->code }})</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="mb-5 bg-rose-50 dark:bg-rose-500/10 border border-rose-200/60 dark:border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs p-4 rounded-xl">
                        <ul class="list-disc pl-4 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('courses.sessions.update', [$course, $session]) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Judul Pertemuan</label>
                        <input type="text" name="title" value="{{ old('title', $session->title) }}"
                            class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tanggal Pertemuan</label>
                        <input type="date" name="session_date" value="{{ old('session_date', $session->session_date->format('Y-m-d')) }}"
                            class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" required>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Jam Mulai</label>
                            <input type="time" name="start_time" value="{{ old('start_time', substr($session->start_time, 0, 5)) }}"
                                class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Jam Selesai</label>
                            <input type="time" name="end_time" value="{{ old('end_time', substr($session->end_time, 0, 5)) }}"
                                class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" required>
                        </div>
                    </div>

                    <div class="pt-3 flex items-center justify-between gap-3 border-t border-slate-100 dark:border-slate-700/60">
                        <a href="{{ route('attendance.take', $session) }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                            Ambil absensi sesi ini &rarr;
                        </a>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('courses.show', $course) }}"
                                class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                                Batal
                            </a>
                            <button type="submit"
                                class="bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white text-xs font-bold rounded-xl px-5 py-2 shadow-md hover:shadow-indigo-500/25 transition-all">
                                Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
