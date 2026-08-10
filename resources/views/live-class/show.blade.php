@extends('layouts.dashboard')

@section('title', $meeting->title)

@php($active = 'Live Class'); @endphp

@section('page')
    <div class="space-y-4">
        {{-- HEADER BAR --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/60">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-lg font-extrabold text-slate-900 dark:text-white">{{ $meeting->title }}</h1>
                    @if ($meeting->status === 'live')
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500 text-white">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> LIVE NOW
                        </span>
                    @elseif ($meeting->status === 'ended')
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                            SELESAI
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                            TERJADWAL
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ $meeting->course->name }} ({{ $meeting->course->code }})</p>
            </div>

            <div class="flex items-center gap-2 self-end sm:self-auto">
                <a href="{{ route('live-class.index') }}" class="text-xs font-semibold text-slate-600 dark:text-slate-300 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    &larr; Kembali
                </a>
                
                @if ($meeting->status === 'live')
                    <form method="POST" action="{{ route('live-class.end', $meeting) }}">
                        @csrf
                        <button type="submit" class="text-xs font-bold text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-500/10 px-3.5 py-2 rounded-xl hover:bg-rose-100 transition">
                            Akhiri Meeting
                        </button>
                    </form>
                @elseif($meeting->status !== 'ended')
                    <form method="POST" action="{{ route('live-class.start', $meeting) }}">
                        @csrf
                        <button type="submit" class="text-xs font-bold text-white bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-2 rounded-xl shadow-sm hover:shadow-indigo-500/25 transition">
                            ▶ Mulai Meeting
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- MAIN CONTENT GRID --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- VIDEO CONTAINER (COL 2) --}}
            <div class="lg:col-span-2 space-y-2">
                <div class="bg-slate-950 rounded-2xl overflow-hidden border border-slate-800 shadow-lg flex items-center justify-center relative" style="aspect-ratio: 16/9;" id="videoContainer">
                    @if ($meeting->status === 'ended')
                        <div class="text-center p-6 text-slate-400">
                            <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-slate-500 mx-auto mb-3 text-lg">🏁</div>
                            <p class="font-bold text-slate-200 text-sm">Meeting ini telah selesai diakhiri.</p>
                            <p class="text-xs text-slate-500 mt-1">Data presensi telah tersimpan secara otomatis di modul Attendance.</p>
                        </div>
                    @else
                        <div id="preJoin" class="text-center p-6 max-w-sm">
                            <div class="w-12 h-12 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center mx-auto mb-3 text-xl">👤</div>
                            <p class="text-slate-200 text-xs font-bold mb-3">Pilih identitas Anda sebelum masuk ke video call:</p>
                            <select id="joinAsSelect" class="w-full bg-slate-900 border border-slate-700 text-slate-100 rounded-xl px-3 py-2.5 text-xs mb-4 focus:outline-none focus:border-indigo-500">
                                <option value="">-- pilih nama siswa --</option>
                                @foreach ($participants as $p)
                                    <option value="{{ $p->id }}" data-name="{{ $p->name }}">{{ $p->name }} ({{ $p->student_code }})</option>
                                @endforeach
                            </select>
                            
                            <button id="joinBtn" class="w-full bg-gradient-to-r from-indigo-600 to-indigo-500 text-white text-xs font-bold rounded-xl py-3 shadow-md hover:shadow-indigo-500/25 transition">
                                📹 Gabung Video Call
                            </button>
                            
                            @if (! $meeting->course_session_id)
                                <p class="text-amber-400 text-[11px] mt-3">
                                    ⚠️ Meeting ini tidak terhubung ke sesi kelas, kehadiran tidak akan tercatat otomatis.
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="p-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700/60 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    <span>💡</span>
                    <span>Video call ditenagai <strong>Jitsi Meet API</strong> (mendukung kamera/mic sungguhan).</span>
                </div>
            </div>

            {{-- RIGHT COLUMN: PARTICIPANTS & CHAT (COL 1) --}}
            <div class="space-y-4">

                {{-- PARTICIPANTS WIDGET --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700/60">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-extrabold text-xs text-slate-800 dark:text-white uppercase tracking-wider">
                            Partisipan Kelas ({{ $participants->count() }})
                        </h2>
                    </div>
                    <div class="space-y-2 max-h-40 overflow-y-auto">
                        @forelse ($participants as $student)
                            <div class="flex items-center gap-2.5 p-1.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/40 transition">
                                <img src="{{ $student->avatar ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($student->name) }}" 
                                     class="w-7 h-7 rounded-full object-cover shrink-0" alt="">
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 truncate">{{ $student->name }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 dark:text-slate-500">Belum ada siswa terdaftar.</p>
                        @endforelse
                    </div>
                </div>

                {{-- CHAT WIDGET --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700/60 flex flex-col" style="height: 340px;">
                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100 dark:border-slate-700/60">
                        <h2 class="font-extrabold text-xs text-slate-800 dark:text-white uppercase tracking-wider">Obrolan Kelas Live</h2>
                        <span class="text-[10px] text-emerald-500 font-bold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Chat Active
                        </span>
                    </div>

                    <div id="chatMessages" class="flex-1 overflow-y-auto space-y-3 mb-3 pr-1">
                        {{-- Filled via JS --}}
                    </div>

                    <form id="chatForm" class="flex gap-2">
                        <input type="text" id="chatInput" placeholder="Tulis pesan..."
                               class="flex-1 bg-slate-50 dark:bg-slate-900/60 dark:text-slate-100 border border-slate-200/80 dark:border-slate-700 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" required>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl px-3 py-2 transition">
                            Kirim
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

<script src="https://meet.jit.si/external_api.js"></script>
<script>
(function () {
    const meetingId = {{ $meeting->id }};
    const roomCode = @json($meeting->room_code);
    const checkInUrl = "{{ route('live-class.check-in', $meeting) }}";
    const checkOutUrl = "{{ route('live-class.check-out', $meeting) }}";
    const csrfToken = "{{ csrf_token() }}";

    let currentStudentId = null;
    let jitsiApi = null;

    function postJson(url, body, keepalive) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(body),
            keepalive: !!keepalive,
        });
    }

    const joinBtn = document.getElementById('joinBtn');
    if (joinBtn) {
        joinBtn.addEventListener('click', function () {
            const select = document.getElementById('joinAsSelect');
            const studentId = select.value;
            const studentName = select.selectedOptions[0]?.dataset.name;

            if (!studentId) {
                alert('Pilih dulu identitas Anda sebelum bergabung.');
                return;
            }

            currentStudentId = studentId;
            sessionStorage.setItem('live_class_student_' + meetingId, studentId);

            const container = document.getElementById('videoContainer');
            container.innerHTML = '';

            jitsiApi = new JitsiMeetExternalAPI('meet.jit.si', {
                roomName: roomCode,
                parentNode: container,
                width: '100%',
                height: '100%',
                userInfo: { displayName: studentName },
                configOverwrite: { prejoinPageEnabled: false },
            });

            jitsiApi.addEventListener('videoConferenceJoined', function () {
                postJson(checkInUrl, { student_id: currentStudentId });
            });

            jitsiApi.addEventListener('videoConferenceLeft', function () {
                postJson(checkOutUrl, { student_id: currentStudentId }, true);
            });
        });
    }

    window.addEventListener('beforeunload', function () {
        if (currentStudentId) {
            postJson(checkOutUrl, { student_id: currentStudentId }, true);
        }
    });
})();
</script>

<script>
(function () {
    const meetingId = {{ $meeting->id }};
    const messagesUrl = "{{ route('live-class.messages.index', $meeting) }}";
    const storeUrl = "{{ route('live-class.messages.store', $meeting) }}";
    const csrfToken = "{{ csrf_token() }}";

    function getSenderName() {
        let name = sessionStorage.getItem('chat_sender_name');
        if (!name) {
            const joinedSelect = document.getElementById('joinAsSelect');
            const joinedName = joinedSelect?.selectedOptions[0]?.dataset.name;
            name = joinedName || prompt('Nama Anda untuk chat ini:', 'Admin') || 'Admin';
            sessionStorage.setItem('chat_sender_name', name);
        }
        return name;
    }

    function renderMessages(messages) {
        const container = document.getElementById('chatMessages');
        if (!container) return;

        if (messages.length === 0) {
            container.innerHTML = `<p class="text-xs text-slate-400 text-center py-6">Belum ada pesan chat.</p>`;
            return;
        }

        container.innerHTML = messages.map(function (m) {
            const time = new Date(m.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            return `<div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700/40">
                <div class="flex items-center justify-between mb-0.5">
                    <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400">${escapeHtml(m.sender_name)}</span>
                    <span class="text-[9px] text-slate-400">${time}</span>
                </div>
                <p class="text-xs text-slate-700 dark:text-slate-200 leading-relaxed">${escapeHtml(m.message)}</p>
            </div>`;
        }).join('');
        container.scrollTop = container.scrollHeight;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function fetchMessages() {
        fetch(messagesUrl)
            .then(function (res) { return res.json(); })
            .then(renderMessages)
            .catch(function () {});
    }

    const chatForm = document.getElementById('chatForm');
    if (chatForm) {
        chatForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            if (!message) return;

            fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ sender_name: getSenderName(), message: message }),
            }).then(function () {
                input.value = '';
                fetchMessages();
            });
        });

        fetchMessages();
        setInterval(fetchMessages, 4000);
    }
})();
</script>
@endsection
