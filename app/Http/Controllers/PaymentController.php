<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Student;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['student', 'course'])->latest();

        $user = auth()->user();
        if ($user && $user->role === 'student') {
            $studentProfile = Student::where('user_id', $user->id)->first();
            if ($studentProfile) {
                $query->where('student_id', $studentProfile->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {
                $q->where('invoice_id', 'like', "%{$searchTerm}%")
                  ->orWhereHas('student', function($sq) use ($searchTerm) {
                      $sq->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        $payments = $query->get();
        $students = Student::all();
        $courses = Course::all();

        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $totalPaidCount = Payment::where('status', 'paid')->count();
        $totalPendingCount = Payment::where('status', 'pending')->count();

        return view('payments.index', compact(
            'payments',
            'students',
            'courses',
            'totalRevenue',
            'totalPaidCount',
            'totalPendingCount'
        ));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user && $user->role === 'student') {
            $studentProfile = Student::where('user_id', $user->id)->firstOrFail();
            $request->merge(['student_id' => $studentProfile->id]);
        }

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'course_id' => 'nullable|exists:courses,id',
            'amount' => 'required|numeric|min:1000|max:99999999.99',
            'payment_method' => 'required|string|max:100',
        ]);

        $maxId = Payment::max('id') ?? 0;
        $invoiceId = 'INV-' . date('Ymd') . '-' . str_pad($maxId + 1, 4, '0', STR_PAD_LEFT);

        Payment::create([
            'invoice_id' => $invoiceId,
            'student_id' => $request->student_id,
            'course_id' => $request->course_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'status' => 'pending',
        ]);

        return redirect()->route('payments.index')->with('success', 'Transaksi pembayaran berhasil ditambahkan!');
    }

    public function uploadProof(Request $request, Payment $payment)
    {
        if (auth()->user()->role === 'student') {
            $studentProfile = Student::where('user_id', auth()->id())->first();
            abort_if(! $studentProfile || $payment->student_id !== $studentProfile->id, 403, 'Akses ditolak: Anda hanya dapat mengunggah bukti pembayaran Anda sendiri.');
        }

        abort_if($payment->status === 'paid', 422, 'Pembayaran ini sudah berstatus lunas.');

        $request->validate([
            'proof_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // Max 5MB
        ]);

        if ($request->hasFile('proof_file')) {
            // Store proof in storage/app/public/payment_proofs
            $storedPath = $request->file('proof_file')->store('payment_proofs', 'public');

            $payment->update([
                'proof_path' => $storedPath,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        return redirect()->route('payments.index')->with('success', 'Bukti pembayaran ' . $payment->invoice_id . ' berhasil diunggah!');
    }

    public function exportInvoice(Payment $payment)
    {
        if (auth()->user()->role === 'student') {
            $studentProfile = Student::where('user_id', auth()->id())->first();
            abort_if(! $studentProfile || $payment->student_id !== $studentProfile->id, 403, 'Akses ditolak: Anda hanya dapat mengunduh invoice Anda sendiri.');
        }

        $filename = 'invoices/Invoice-' . $payment->invoice_id . '.html';
        
        $studentName = e($payment->student->name ?? 'Siswa');
        $courseName = e($payment->course->name ?? 'Course Fee');
        $paymentMethod = e($payment->payment_method);
        $invoiceId = e($payment->invoice_id);
        $amountFormatted = number_format($payment->amount, 0, ',', '.');
        $dateFormatted = optional($payment->paid_at ?? $payment->created_at)->format('d M Y, H:i');

        $htmlContent = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Struk Invoice - {$invoiceId}</title>
            <style>
                body { font-family: sans-serif; padding: 30px; color: #1e293b; line-height: 1.6; }
                .header { border-bottom: 2px solid #6366f1; padding-bottom: 15px; margin-bottom: 20px; }
                .title { font-size: 24px; font-weight: bold; color: #6366f1; }
                .details { margin-bottom: 20px; font-size: 14px; }
                .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                .table th, .table td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; }
                .table th { background: #f8fafc; }
                .footer { margin-top: 30px; font-size: 12px; color: #94a3b8; text-align: center; }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='title'>SkillSet Academy - Invoice Struk Resmi</div>
                <p>Invoice ID: <strong>{$invoiceId}</strong> &middot; Tanggal: {$dateFormatted}</p>
            </div>
            <div class='details'>
                <p><strong>Nama Siswa:</strong> {$studentName}</p>
                <p><strong>Metode Pembayaran:</strong> {$paymentMethod}</p>
                <p><strong>Status:</strong> <span style='color:green;font-weight:bold;'>".strtoupper($payment->status)."</span></p>
            </div>
            <table class='table'>
                <thead>
                    <tr><th>Item / Deskripsi</th><th>Jumlah Nominal</th></tr>
                </thead>
                <tbody>
                    <tr><td>Pembayaran Kelas: {$courseName}</td><td>Rp {$amountFormatted}</td></tr>
                </tbody>
            </table>
            <div class='footer'>Terima kasih atas pembayaran Anda. Dokumen ini disahkan secara digital oleh SkillSet Platform.</div>
        </body>
        </html>
        ";

        // Store invoice file in project storage
        Storage::disk('public')->put($filename, $htmlContent);
        $payment->update(['invoice_path' => $filename]);

        return Storage::disk('public')->download($filename, 'Struk-' . $payment->invoice_id . '.html');
    }
}
