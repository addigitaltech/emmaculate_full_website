<?php

namespace App\Http\Controllers;

use App\Domain\Payments\ReceiptService;
use App\Models\PaymentReceipt;
use Illuminate\Http\Request;

/** A printable receipt for a verified payment. Only the payer, the student, the student's parents and finance staff may open it. */
class ReceiptController extends Controller
{
    public function show(Request $request, PaymentReceipt $receipt, ReceiptService $receipts)
    {
        $receipt->load('transaction.student');
        $transaction = $receipt->transaction;
        abort_unless($transaction && $transaction->status === 'successful', 404);

        $user = $request->user();
        $student = $transaction->student;
        $allowed = $user->hasRole('Super Admin') || $user->can('manage payments')
            || (int) $transaction->initiated_by === (int) $user->id
            || ($user->hasRole('Student') && $student && (int) $student->user_id === (int) $user->id)
            || ($user->hasRole('Parent') && $student && $student->parents()->where('parents.user_id', $user->id)->exists());
        abort_unless($allowed, 403);

        if ($request->query('download') === 'pdf') {
            return response($receipts->pdf($receipt), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="receipt-'.preg_replace('/[^A-Za-z0-9\-]/', '', $receipt->receipt_number).'.pdf"',
            ]);
        }

        return view('portal.receipt', $receipts->data($receipt));
    }
}
