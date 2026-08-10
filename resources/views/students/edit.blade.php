@extends('layouts.dashboard')

@section('title', 'Edit Data Siswa')

@php($active = 'Students'); @endphp

@section('page')
    <div class="max-w-xl mx-auto space-y-6">
        <div>
            <a href="{{ route('students.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline mb-3">
                <span>&larr; Kembali ke Master Students</span>
            </a>

            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-lg shrink-0">
                        ✏️
                    </div>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">Edit Data Siswa</h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Perbarui profil, informasi kontak, dan status siswa {{ $student->name }}.</p>
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

                <form method="POST" action="{{ route('students.update', $student->id) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nama Lengkap Siswa</label>
                        <input type="text" name="name" value="{{ old('name', $student->name ?? $student->user?->name) }}"
                            placeholder="Nama Lengkap"
                            class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            required>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tanggal Lahir</label>
                            <input type="date" name="date_birth" value="{{ old('date_birth', $student->date_birth) }}"
                                class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                                required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nomor Telepon / WhatsApp</label>
                            <input type="tel" name="tel" value="{{ old('tel', $student->tel) }}" placeholder="08123456789"
                                class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                                required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Alamat Email Login</label>
                        <input type="email" name="email"
                            value="{{ old('email', $student->email ?? $student->user?->email) }}"
                            placeholder="email@example.com"
                            class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Status Keaktifan</label>
                        <select name="status" id="status"
                            class="w-full bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            required>
                            <option value="regular" {{ old('status', $student->status) === 'regular' ? 'selected' : '' }}>
                                Regular (Presensi Aktif)
                            </option>
                            <option value="irregular" {{ old('status', $student->status) === 'irregular' ? 'selected' : '' }}>
                                Irregular (Perlu Perhatian)
                            </option>
                        </select>
                    </div>

                    <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-700/60">
                        <a href="{{ route('students.index') }}"
                            class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                            Batal
                        </a>
                        <button type="submit"
                            class="bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white text-xs font-bold rounded-xl px-6 py-2.5 shadow-md hover:shadow-indigo-500/25 transition-all">
                            Update Data Siswa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
