@extends('layouts.dashboard')

@section('title', 'Buat Meeting Baru')

@php($active = 'Live Class'); @endphp

@section('page')
    <div class="max-w-xl mx-auto space-y-6">
        <div>
            <a href="{{ route('live-class.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline mb-3">
                <span>&larr; Kembali ke Live Class</span>
            </a>
            
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-lg shrink-0">
                        🎥
                    </div>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Buat Meeting Live Baru</h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Jadwalkan sesi video conference & presensi otomatis kelas.</p>
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

                <form method="POST" action="{{ route('live-class.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Judul Meeting / Pertemuan</label>
                        <input type="text" name="title" value="{{ old('title') }}" required
                            placeholder="Misal: Live Class Web Dev - Pertemuan 1"
                            class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Pilih Course / Kelas</label>
                        <select id="courseSelect" name="course_id" required
                            class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                            <option value="">-- Pilih kelas --</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                    {{ $course->name }} ({{ $course->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Hubungkan ke Sesi Kelas (Opsional untuk Auto-Presensi)
                        </label>
                        <select id="sessionSelect" name="course_session_id"
                            class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                            <option value="">Tanpa sesi (Video call biasa tanpa auto-presensi)</option>
                            @foreach ($sessions as $session)
                                <option value="{{ $session->id }}" data-course="{{ $session->course_id }}"
                                    {{ old('course_session_id') == $session->id ? 'selected' : '' }}>
                                    {{ $session->title }} — {{ $session->session_date->translatedFormat('d M Y') }}
                                </option>
                            @endforeach
                        </select>
                        <div class="mt-2 p-3 bg-indigo-50/60 dark:bg-indigo-950/30 rounded-xl border border-indigo-100 dark:border-indigo-500/20 text-[11px] text-indigo-700 dark:text-indigo-300">
                            💡 <strong>Auto-Presensi:</strong> Siswa yang bergabung ke video call ini akan otomatis tercatat <strong class="text-emerald-600 dark:text-emerald-400">"Hadir"</strong> pada modul Attendance.
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Jadwal Tanggal & Jam</label>
                            <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" required
                                class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Estimasi Durasi (Menit)</label>
                            <input type="number" name="duration_minutes" value="{{ old('duration_minutes', 60) }}"
                                min="15" max="240" required
                                class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                        </div>
                    </div>

                    <div class="pt-3">
                        <button type="submit"
                            class="w-full bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white text-xs font-bold rounded-xl py-3 shadow-md hover:shadow-indigo-500/25 transition-all">
                            Buat Meeting Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const courseSelect = document.getElementById('courseSelect');
        const sessionSelect = document.getElementById('sessionSelect');
        const sessionOptions = Array.from(sessionSelect.options).slice(1);

        function filterSessions() {
            const courseId = courseSelect.value;
            sessionOptions.forEach(function(opt) {
                const match = !courseId || opt.dataset.course === courseId;
                opt.hidden = !match;
                if (!match && opt.selected) sessionSelect.value = '';
            });
        }

        if (courseSelect) {
            courseSelect.addEventListener('change', filterSessions);
            filterSessions();
        }
    </script>
@endsection
