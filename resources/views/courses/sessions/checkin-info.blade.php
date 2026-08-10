@extends('layouts.dashboard')

@section('title', 'QR Check-in Info - ' . $session->title)

@php($active = 'Courses'); @endphp

@section('page')
    <div class="max-w-md mx-auto space-y-6">
        <div>
            <a href="{{ route('courses.show', $course) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline mb-3">
                <span>&larr; Kembali ke {{ $course->name }}</span>
            </a>

            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60 text-center space-y-4">
                <div>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200/40">
                        {{ $course->name }}
                    </span>
                    <h1 class="font-black text-xl text-slate-900 dark:text-white mt-1.5">{{ $session->title }}</h1>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 flex items-center justify-center gap-2">
                        <span>📅 {{ $session->session_date->translatedFormat('d M Y') }}</span>
                        <span>&middot;</span>
                        <span>⏰ {{ $session->start_time }} - {{ $session->end_time }}</span>
                    </p>
                </div>

                @php
                    $checkinUrl = route('checkin.show', $session->checkin_code);
                    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . urlencode($checkinUrl);
                @endphp

                <div class="p-4 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-100 dark:border-slate-700/60 inline-block">
                    <img src="{{ $qrUrl }}" alt="QR Check-in"
                        class="mx-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-white p-2" width="220" height="220">
                </div>

                <div>
                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Scan QR Code di atas, atau salin link publik presensi:</p>

                    <div class="flex items-center gap-2">
                        <input id="checkinLink" type="text" readonly value="{{ $checkinUrl }}"
                            class="flex-1 bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-700 dark:text-slate-100 rounded-xl px-3 py-2.5 text-xs font-mono">
                        <button onclick="copyLink()"
                            class="text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl px-4 py-2.5 shadow-sm transition shrink-0">
                            Salin Link
                        </button>
                    </div>
                    <p id="copiedNote" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hidden mt-2">✅ Link presensi berhasil disalin!</p>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-700/60 text-[11px] text-slate-400 dark:text-slate-500">
                    💡 Check-in setelah <strong>{{ config('attendance.late_tolerance_minutes', 15) }} menit</strong> dari jam mulai otomatis berstatus <strong>"Telat"</strong>.
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyLink() {
            const input = document.getElementById('checkinLink');
            input.select();
            navigator.clipboard.writeText(input.value).then(function() {
                document.getElementById('copiedNote').classList.remove('hidden');
                setTimeout(() => document.getElementById('copiedNote').classList.add('hidden'), 2500);
            });
        }
    </script>
@endsection
