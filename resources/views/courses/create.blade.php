@extends('layouts.dashboard')

@section('title', 'Tambah Course Baru')

@php($active = 'Courses'); @endphp

@section('page')
    <div class="max-w-xl mx-auto space-y-6">
        <div>
            <a href="{{ route('courses.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline mb-3">
                <span>&larr; Kembali ke Master Courses</span>
            </a>

            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-lg shrink-0">
                        📚
                    </div>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Tambah Course Baru</h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Buat kelas / mata pelajaran baru dalam katalog akademik.</p>
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

                <form method="POST" action="{{ route('courses.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nama Kelas / Course</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            placeholder="Misal: Pemrograman Web Lanjut"
                            class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Kategori Course</label>
                            <input type="text" name="categories" value="{{ old('categories') }}" required
                                placeholder="Misal: Web, Mobile, Data"
                                class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Kode Kelas Unik</label>
                            <input type="text" name="code" value="{{ old('code') }}" required
                                placeholder="Misal: WEB-201"
                                class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Guru Pengampu Kelas (Opsional)</label>
                        <select name="teacher_id" class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                            <option value="">-- Belum ditentukan --</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->name }} ({{ $teacher->specialization ?? 'Guru' }})
                                </option>
                            @endforeach
                        </select>
                        @if ($teachers->isEmpty())
                            <p class="text-[11px] text-amber-500 mt-1">
                                Belum ada data guru. <a href="{{ route('teachers.create') }}" class="underline font-bold">Tambah guru</a> dulu jika diperlukan.
                            </p>
                        @endif
                    </div>

                    <div class="p-3 bg-indigo-50/60 dark:bg-indigo-950/30 rounded-xl border border-indigo-100 dark:border-indigo-500/20 text-[11px] text-indigo-700 dark:text-indigo-300 leading-relaxed">
                        ℹ️ <strong>Langkah Selanjutnya:</strong> Setelah course berhasil dibuat, Anda dapat mendaftarkan siswa ke kelas ini dan menambahkan sesi pertemuan untuk presensi.
                    </div>

                    <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-700/60">
                        <a href="{{ route('courses.index') }}"
                            class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                            Batal
                        </a>
                        <button type="submit"
                            class="bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white text-xs font-bold rounded-xl px-6 py-2.5 shadow-md hover:shadow-indigo-500/25 transition-all">
                            Simpan Course
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
