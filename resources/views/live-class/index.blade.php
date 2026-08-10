@extends('layouts.dashboard')

@section('title', 'Live Class')

@php($active = 'Live Class'); @endphp

@section('page')
    {{-- TOP BAR --}}
    <div class="flex items-center justify-between mb-6">
        <form method="GET" action="{{ route('live-class.index') }}" class="relative w-96">
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
            {{ session('success') ?? session('status') ?? 'Live meeting berhasil diproses.' }}
        </div>
    @endif

    {{-- HEADER ROW --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Live Class</h1>
        <a href="{{ route('live-class.create') }}"
           class="bg-indigo-500 hover:bg-indigo-600 text-white text-xs font-bold rounded-xl px-4 py-2.5 shadow-sm transition">
            + Create Meeting
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- MEETING ROOM (sedang live) --}}
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60">
            <div class="flex items-center justify-between mb-5">
                <h2 class="font-bold text-base text-slate-900 dark:text-white">Meeting Room</h2>
                <span class="text-slate-300 dark:text-slate-600 text-lg cursor-pointer">⋮</span>
            </div>

            @if ($liveMeeting)
                <div class="border border-rose-200/80 dark:border-rose-500/30 bg-rose-50/50 dark:bg-rose-500/10 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                        <span class="text-xs font-extrabold text-rose-500 dark:text-rose-400">LIVE SEKARANG</span>
                    </div>
                    <p class="font-bold text-base text-slate-900 dark:text-white">{{ $liveMeeting->title }}</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mb-5">{{ $liveMeeting->course->name ?? 'Course' }}</p>
                    <a href="{{ route('live-class.show', $liveMeeting) }}"
                       class="block text-center bg-indigo-500 text-white text-xs font-bold rounded-xl py-3 hover:bg-indigo-600 shadow-sm transition">
                        Gabung Meeting
                    </a>
                </div>
            @else
                <div class="py-10 text-center text-xs text-slate-400 dark:text-slate-500">
                    Tidak ada meeting yang sedang berlangsung.
                </div>
            @endif
        </div>

        {{-- WEEKLY MEETINGS --}}
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60">
            <div class="flex items-center justify-between mb-5">
                <h2 class="font-bold text-base text-slate-900 dark:text-white">Weekly Meetings</h2>
                <span class="text-slate-300 dark:text-slate-600 text-lg cursor-pointer">⋮</span>
            </div>

            <div class="space-y-3 max-h-[380px] overflow-y-auto">
                @forelse ($upcomingMeetings as $meeting)
                    <div class="flex items-center justify-between border border-slate-100 dark:border-slate-700/60 rounded-2xl px-4 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-700/40 transition">
                        <div>
                            <p class="font-bold text-xs text-slate-800 dark:text-slate-100">{{ $meeting->title }}</p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">{{ $meeting->scheduled_at->translatedFormat('d M Y, H:i') }}</p>
                        </div>
                        <form method="POST" action="{{ route('live-class.start', $meeting) }}">
                            @csrf
                            <button class="text-xs font-bold text-indigo-500 border border-indigo-200 dark:border-indigo-500/30 rounded-xl px-3.5 py-1.5 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition">
                                Mulai
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="py-10 text-center text-xs text-slate-400 dark:text-slate-500">
                        Belum ada jadwal meeting mendatang.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
