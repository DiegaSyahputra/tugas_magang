@extends('layouts.dashboard')

@section('title', 'Courses')

@php($active = 'Courses'); @endphp

@section('page')
    {{-- TOP BAR --}}
    <div class="flex items-center justify-between mb-6">
        <form method="GET" action="{{ route('courses.index') }}" class="relative w-96">
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
            <img src="{{ 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode(auth()->user()->name ?? 'User') }}"
                class="w-9 h-9 rounded-full border border-slate-100 dark:border-slate-700 object-cover shadow-sm" alt="avatar"
                title="{{ auth()->user()->name ?? 'User' }} ({{ ucfirst(auth()->user()->role ?? 'guest') }})">
        </div>
    </div>

    {{-- ALERT BANNER --}}
    @if (session('success') || session('status'))
        <div class="mb-6 bg-emerald-50/90 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-sm px-4 py-3 rounded-2xl font-medium border border-emerald-100 dark:border-emerald-500/20">
            {{ session('success') ?? session('status') ?? 'Data course berhasil diproses.' }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- COURSES MAIN CARD --}}
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60 flex flex-col justify-between min-h-[580px]">
            <div>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="font-bold text-lg text-slate-900 dark:text-white">Courses</h2>
                    <div class="flex items-center gap-3">
                        @if (in_array(auth()->user()->role, ['admin', 'teacher']))
                            <a href="{{ route('courses.create') }}"
                                class="bg-indigo-500 hover:bg-indigo-600 text-white text-xs font-bold rounded-xl px-4 py-2.5 shadow-sm transition">
                                + Create Now
                            </a>
                        @endif
                    </div>
                </div>

                {{-- COURSES LIST --}}
                <div class="space-y-3">
                    @forelse ($courses as $course)
                        <a href="{{ route('courses.show', $course) }}"
                            class="flex flex-col sm:flex-row sm:items-center justify-between border border-slate-100 dark:border-slate-700/60 rounded-2xl px-5 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/40 hover:border-indigo-200 dark:hover:border-indigo-500/30 transition-all gap-3">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                                        {{ $course->code }}
                                    </span>
                                    <span class="text-xs font-semibold text-slate-400">{{ $course->categories ?? 'General' }}</span>
                                </div>
                                <p class="font-bold text-slate-800 dark:text-slate-100 text-sm">{{ $course->name }}</p>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                                    @if ($course->teacher)
                                        👨‍🏫 {{ $course->teacher->name }}
                                    @else
                                        ⚠️ <span class="text-amber-500">Belum ada pengajar</span>
                                    @endif
                                </p>
                            </div>
                            
                            <div class="flex items-center gap-5 text-xs text-slate-500 dark:text-slate-400 shrink-0 font-medium">
                                <span class="bg-slate-100 dark:bg-slate-700 px-2.5 py-1 rounded-full text-slate-600 dark:text-slate-300">👥 {{ $course->students_count }} siswa</span>
                                <span class="bg-slate-100 dark:bg-slate-700 px-2.5 py-1 rounded-full text-slate-600 dark:text-slate-300">📅 {{ $course->sessions_count }} sesi</span>
                                <span class="text-indigo-500 font-bold hover:underline">Kelola &rarr;</span>
                            </div>
                        </a>
                    @empty
                        <div class="py-12 text-center text-xs text-slate-400 dark:text-slate-500">
                            Belum ada course terdaftar.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- TABLE FOOTER PAGINATION --}}
            <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-700/60 mt-4 text-xs text-slate-400">
                <span>Showing 1 to {{ count($courses) }} of {{ count($courses) }} results</span>
                
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

            {{-- CATEGORIES OVERVIEW WIDGET --}}
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="font-bold text-base text-slate-900 dark:text-white">Course Categories</h2>
                    <span class="text-slate-300 dark:text-slate-600 text-lg cursor-pointer">⋮</span>
                </div>
                <div class="space-y-3">
                    @php
                        $categories = $courses->pluck('categories')->filter()->unique();
                    @endphp
                    @forelse($categories as $cat)
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-100 dark:border-slate-700">
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">🏷️ {{ $cat }}</span>
                            <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-500/10">
                                {{ $courses->where('categories', $cat)->count() }} Course
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 dark:text-slate-500 py-4 text-center">Belum ada kategori khusus.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
@endsection
