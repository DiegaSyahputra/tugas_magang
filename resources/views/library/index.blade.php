@extends('layouts.dashboard')

@section('title', 'Library')

@php($active = 'Library'); @endphp

@section('page')
    {{-- TOP BAR --}}
    <div class="flex items-center justify-between mb-6">
        <form method="GET" action="{{ route('library.index') }}" class="relative w-96">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search e-books, modules, materials.."
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

    {{-- ALERT BANNER --}}
    @if (session('success') || session('status'))
        <div class="mb-6 bg-emerald-50/90 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-sm px-4 py-3 rounded-2xl font-medium border border-emerald-100 dark:border-emerald-500/20">
            {{ session('success') ?? session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 text-sm px-4 py-3 rounded-2xl font-medium border border-rose-100 dark:border-rose-500/20">
            {{ session('error') }}
        </div>
    @endif

    {{-- HEADER & FILTER BAR --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Digital Learning Library</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Katalog e-book, modul PDF, dan materi digital pembelajaran.</p>
        </div>

        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('library.index') }}">
                <select name="category" onchange="this.form.submit()"
                    class="text-xs font-semibold border border-slate-200 dark:border-slate-700 dark:bg-slate-700 dark:text-slate-200 rounded-xl px-3 py-2 text-slate-500 focus:outline-none">
                    <option value="all">Semua Kategori ▾</option>
                    <option value="Web Development" {{ request('category') === 'Web Development' ? 'selected' : '' }}>Web Development</option>
                    <option value="UI/UX Design" {{ request('category') === 'UI/UX Design' ? 'selected' : '' }}>UI/UX Design</option>
                    <option value="Mobile Apps" {{ request('category') === 'Mobile Apps' ? 'selected' : '' }}>Mobile Apps</option>
                    <option value="Data Science" {{ request('category') === 'Data Science' ? 'selected' : '' }}>Data Science</option>
                </select>
            </form>

            @if (in_array(auth()->user()->role, ['admin', 'teacher']))
                <button onclick="document.getElementById('uploadLibraryModal').classList.remove('hidden')"
                    class="bg-indigo-500 hover:bg-indigo-600 text-white text-xs font-bold rounded-xl px-4 py-2.5 shadow-sm transition">
                    + Upload Material
                </button>
            @endif
        </div>
    </div>

    {{-- MATERIALS GRID --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($resources as $item)
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60 flex flex-col justify-between hover:border-indigo-200 dark:hover:border-indigo-500/30 transition-all group">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-500 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                            {{ $item->cover_icon }}
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                            {{ $item->file_format }}
                        </span>
                    </div>

                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-500">{{ $item->category }}</span>
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white mt-1 group-hover:text-indigo-600 transition-colors leading-snug">
                        {{ $item->title }}
                    </h3>

                    <div class="flex items-center gap-3 mt-3 text-xs text-slate-400">
                        <span>📦 {{ $item->file_size }}</span>
                        <span>&middot;</span>
                        <span>📥 {{ $item->downloads }} unduhan</span>
                        <span>&middot;</span>
                        <span class="text-amber-500 font-bold">★ {{ $item->rating }}</span>
                    </div>
                </div>

                <div class="pt-5 mt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center gap-2">
                    <a href="{{ route('library.download', $item->id) }}" target="_blank"
                        class="flex-1 text-center bg-indigo-500 hover:bg-indigo-600 text-white text-xs font-bold py-2 rounded-xl shadow-sm transition">
                        📥 Download Berkas
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-3 py-12 text-center text-xs text-slate-400">
                Tidak ada materi digital ditemukan untuk kriteria ini.
            </div>
        @endforelse
    </div>

    {{-- MODAL UPLOAD MATERIAL --}}
    <div id="uploadLibraryModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl border border-slate-100 dark:border-slate-700 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-extrabold text-base text-slate-900 dark:text-white">Upload Materi Digital</h2>
                <button onclick="document.getElementById('uploadLibraryModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <form method="POST" action="{{ route('library.store') }}" enctype="multipart/form-data" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Judul Materi</label>
                    <input type="text" name="title" required placeholder="Misal: Modul Pembelajaran Laravel 11"
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 dark:text-white">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Kategori</label>
                    <select name="category" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 dark:text-white">
                        <option value="Web Development">Web Development</option>
                        <option value="UI/UX Design">UI/UX Design</option>
                        <option value="Mobile Apps">Mobile Apps</option>
                        <option value="Data Science">Data Science</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Tipe Format</label>
                    <input type="text" name="file_format" required placeholder="PDF E-Book / Video / ZIP"
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 dark:text-white">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Pilih Berkas File (Max 20MB)</label>
                    <input type="file" name="file" required
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 dark:text-white text-xs">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('uploadLibraryModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border text-slate-600 dark:text-slate-300">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 text-white font-bold">Simpan Berkas &rarr;</button>
                </div>
            </form>
        </div>
    </div>
@endsection
