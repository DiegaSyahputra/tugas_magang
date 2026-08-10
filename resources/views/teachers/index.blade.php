@extends('layouts.dashboard')

@section('title', 'Teachers')

@php($active = 'Teachers'); @endphp

@section('page')
    {{-- TOP BAR --}}
    <div class="flex items-center justify-between mb-6">
        <form method="GET" action="{{ route('teachers.index') }}" class="relative w-96">
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
    @if (session('status') || session('message') || session('success'))
        <div class="mb-6 bg-emerald-50/90 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-sm px-4 py-3 rounded-2xl font-medium border border-emerald-100 dark:border-emerald-500/20">
            {{ session('message') ?? session('success') ?? 'Data guru berhasil diproses.' }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- TEACHERS TABLE (LEFT MAIN CARD) --}}
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60 flex flex-col justify-between min-h-[580px]">
            <div>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="font-bold text-lg text-slate-900 dark:text-white">Teachers</h2>
                    <div class="flex items-center gap-3">
                        @if (auth()->user()->role === 'admin')
                            <a href="{{ route('teachers.create') }}"
                                class="bg-indigo-500 hover:bg-indigo-600 text-white text-xs font-bold rounded-xl px-4 py-2.5 shadow-sm transition">
                                + Create Now
                            </a>
                        @endif
                    </div>
                </div>

                {{-- DESKTOP & RESPONSIVE TABLE VIEW --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-700/60 font-semibold">
                                <th class="py-3 px-2 w-8"><input type="checkbox" class="rounded border-slate-300 text-indigo-500 focus:ring-indigo-500"></th>
                                <th class="py-3 px-3">Nama</th>
                                <th class="py-3 px-3">Email</th>
                                <th class="py-3 px-3">Telepon</th>
                                <th class="py-3 px-3">Spesialisasi</th>
                                <th class="py-3 px-2 text-center w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-700/40">
                            @forelse ($teachers as $t)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition-colors">
                                    <td class="py-3.5 px-2"><input type="checkbox" class="rounded border-slate-300 text-indigo-500 focus:ring-indigo-500"></td>
                                    <td class="py-3.5 px-3 font-semibold text-slate-800 dark:text-slate-100">
                                        <div class="flex items-center gap-3">
                                            <img src="{{ $t->avatar ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($t->name) }}" 
                                                 class="w-8 h-8 rounded-full object-cover shrink-0 bg-slate-100 border border-slate-100 dark:border-slate-700" alt="">
                                            <span>{{ $t->name }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-3 font-mono text-slate-500 dark:text-slate-400">{{ $t->user?->email ?? '-' }}</td>
                                    <td class="py-3.5 px-3 text-slate-500 dark:text-slate-400">{{ $t->tel ?? '-' }}</td>
                                    <td class="py-3.5 px-3">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                                            {{ $t->specialization ?? 'Guru' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-2 text-center text-slate-400">
                                        @if (auth()->user()->role === 'admin')
                                            <div class="flex items-center justify-center gap-1.5">
                                                <a href="{{ route('teachers.edit', $t->id) }}" title="Edit Data" class="hover:text-indigo-600 text-slate-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                </a>
                                                <form action="{{ route('teachers.destroy', $t->id) }}" method="POST" onsubmit="return confirm('Hapus guru {{ $t->name }}?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="hover:text-rose-500 text-slate-400">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-slate-300">👁️</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400 dark:text-slate-500">Belum ada data guru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TABLE FOOTER PAGINATION --}}
            <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-700/60 mt-4 text-xs text-slate-400">
                <span>Showing 1 to {{ count($teachers) }} of {{ count($teachers) }} results</span>
                
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

            {{-- SPECIALIZATION OVERVIEW WIDGET --}}
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="font-bold text-base text-slate-900 dark:text-white">Teacher Specialization</h2>
                    <span class="text-slate-300 dark:text-slate-600 text-lg cursor-pointer">⋮</span>
                </div>
                <div class="space-y-3">
                    @php
                        $specs = $teachers->pluck('specialization')->filter()->unique();
                    @endphp
                    @forelse($specs as $spec)
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-700/40 border border-slate-100 dark:border-slate-700">
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">🎯 {{ $spec }}</span>
                            <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-500/10">
                                {{ $teachers->where('specialization', $spec)->count() }} Guru
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 dark:text-slate-500 py-4 text-center">Belum ada spesialisasi terdaftar.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
@endsection
