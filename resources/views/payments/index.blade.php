@extends('layouts.dashboard')

@section('title', 'Payments')

@php($active = 'Payments'); @endphp

@section('page')
    {{-- TOP BAR --}}
    <div class="flex items-center justify-between mb-6">
        <form method="GET" action="{{ route('payments.index') }}" class="relative w-96">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search invoice, student name.."
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

    {{-- STAT CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/60 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-2xl shrink-0">
                💰
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total Revenue</p>
                <p class="text-xl font-black text-slate-900 dark:text-white mt-0.5">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/60 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-500 flex items-center justify-center text-2xl shrink-0">
                ✅
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Lunas (Paid)</p>
                <p class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ $totalPaidCount }} Transaksi</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/60 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-500 flex items-center justify-center text-2xl shrink-0">
                ⏳
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Menunggu (Pending)</p>
                <p class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ $totalPendingCount }} Transaksi</p>
            </div>
        </div>
    </div>

    {{-- MAIN PAYMENTS TABLE --}}
    <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60 flex flex-col justify-between min-h-[500px]">
        <div>
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-bold text-lg text-slate-900 dark:text-white">Payments & Invoices</h2>
                <div class="flex items-center gap-3">
                    <form method="GET" action="{{ route('payments.index') }}">
                        <select name="status" onchange="this.form.submit()"
                            class="text-xs font-semibold border border-slate-200 dark:border-slate-700 dark:bg-slate-700 dark:text-slate-200 rounded-xl px-3 py-2 text-slate-500 focus:outline-none">
                            <option value="all">Filter ▾</option>
                            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </form>

                    <button onclick="document.getElementById('createPaymentModal').classList.remove('hidden')"
                        class="bg-indigo-500 hover:bg-indigo-600 text-white text-xs font-bold rounded-xl px-4 py-2.5 shadow-sm transition">
                        + Tambah Transaksi
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead>
                        <tr class="text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-700/60 font-semibold">
                            <th class="py-3 px-2 w-8"><input type="checkbox" class="rounded border-slate-300 text-indigo-500 focus:ring-indigo-500"></th>
                            <th class="py-3 px-3">Invoice ID</th>
                            <th class="py-3 px-3">Siswa</th>
                            <th class="py-3 px-3">Course / Kelas</th>
                            <th class="py-3 px-3">Nominal</th>
                            <th class="py-3 px-3">Metode</th>
                            <th class="py-3 px-3 text-center">Status</th>
                            <th class="py-3 px-2 text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-700/40">
                        @forelse ($payments as $p)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition-colors">
                                <td class="py-3.5 px-2"><input type="checkbox" class="rounded border-slate-300 text-indigo-500 focus:ring-indigo-500"></td>
                                <td class="py-3.5 px-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $p->invoice_id }}</td>
                                <td class="py-3.5 px-3 font-semibold text-slate-800 dark:text-slate-100">
                                    <div class="flex items-center gap-2.5">
                                        <img src="{{ 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($p->student->name ?? 'Student') }}" class="w-7 h-7 rounded-full object-cover shrink-0" alt="">
                                        <span>{{ $p->student->name ?? 'Siswa' }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-slate-600 dark:text-slate-300 font-medium">{{ $p->course->name ?? 'Course Fee' }}</td>
                                <td class="py-3.5 px-3 font-bold text-slate-800 dark:text-white">Rp {{ number_format($p->amount, 0, ',', '.') }}</td>
                                <td class="py-3.5 px-3 text-slate-400 font-mono">{{ $p->payment_method }}</td>
                                <td class="py-3.5 px-3 text-center">
                                    @if ($p->status === 'paid')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                            ● Paid
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-500/10 text-amber-500 dark:text-amber-400">
                                            ● Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-2 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        @if ($p->status === 'pending')
                                            <button onclick="openUploadProof('{{ $p->id }}', '{{ $p->invoice_id }}')" class="text-amber-500 font-bold hover:underline">
                                                Upload Bukti
                                            </button>
                                        @endif
                                        <a href="{{ route('payments.export-invoice', $p->id) }}" class="text-indigo-500 font-bold hover:underline">
                                            Export Struk
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400">Belum ada riwayat pembayaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TABLE FOOTER PAGINATION --}}
        <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-700/60 mt-4 text-xs text-slate-400">
            <span>Showing 1 to {{ count($payments) }} of {{ count($payments) }} results</span>
            
            <div class="flex items-center gap-1">
                <button class="w-8 h-8 rounded-lg bg-slate-800 dark:bg-slate-700 text-white flex items-center justify-center font-bold text-xs hover:bg-slate-700 transition">&lt;</button>
                <button class="w-8 h-8 rounded-lg bg-slate-800 dark:bg-slate-700 text-white flex items-center justify-center font-bold text-xs">1</button>
                <button class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center font-bold text-xs hover:bg-slate-200 transition">2</button>
                <button class="w-8 h-8 rounded-lg bg-slate-800 dark:bg-slate-700 text-white flex items-center justify-center font-bold text-xs hover:bg-slate-700 transition">&gt;</button>
            </div>
        </div>
    </div>

    {{-- MODAL UPLOAD BUKTI BAYAR --}}
    <div id="uploadProofModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl border border-slate-100 dark:border-slate-700 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-extrabold text-base text-slate-900 dark:text-white">Upload Bukti Transfer</h2>
                <button onclick="document.getElementById('uploadProofModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <form id="uploadProofForm" method="POST" action="" enctype="multipart/form-data" class="space-y-3 text-xs">
                @csrf
                <p id="proofInvoiceText" class="font-bold text-indigo-600 dark:text-indigo-400"></p>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Pilih Foto / Berkas Bukti (Max 5MB)</label>
                    <input type="file" name="proof_file" required accept="image/*,application/pdf"
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 dark:text-white text-xs">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('uploadProofModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border text-slate-600 dark:text-slate-300">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 text-white font-bold">Simpan & Verifikasi &rarr;</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL TAMBAH TRANSAKSI --}}
    <div id="createPaymentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl border border-slate-100 dark:border-slate-700 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-extrabold text-base text-slate-900 dark:text-white">Tambah Transaksi Pembayaran</h2>
                <button onclick="document.getElementById('createPaymentModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <form method="POST" action="{{ route('payments.store') }}" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Pilih Siswa</label>
                    <select name="student_id" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 dark:text-white">
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->student_code }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Pilih Course (Opsional)</label>
                    <select name="course_id" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 dark:text-white">
                        <option value="">-- Biaya SPP / Umum --</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->name }} ({{ $course->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nominal Pembayaran (Rp)</label>
                    <input type="number" name="amount" value="750000" required
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 dark:text-white">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Metode Pembayaran</label>
                    <select name="payment_method" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 dark:text-white">
                        <option value="QRIS GoPay">QRIS GoPay</option>
                        <option value="BCA Virtual Account">BCA Virtual Account</option>
                        <option value="Mandiri VA">Mandiri VA</option>
                        <option value="ShopeePay">ShopeePay</option>
                    </select>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('createPaymentModal').classList.add('hidden')" class="px-4 py-2 rounded-xl border text-slate-600 dark:text-slate-300">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 text-white font-bold">Simpan Transaksi &rarr;</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openUploadProof(id, invoiceId) {
            document.getElementById('uploadProofForm').action = '/payments/' + id + '/upload-proof';
            document.getElementById('proofInvoiceText').innerText = 'Invoice: ' + invoiceId;
            document.getElementById('uploadProofModal').classList.remove('hidden');
        }
    </script>
@endsection
