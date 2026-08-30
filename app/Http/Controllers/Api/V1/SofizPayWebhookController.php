<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SofizPay\SofizPayPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SofizPayWebhookController extends Controller
{
    public function __construct(private readonly SofizPayPaymentService $payments) {}

    public function return(Request $request): JsonResponse
    {
        $invoiceId = (string) $request->query('invoice_id', '');
        $cibTransactionId = (string) ($request->query('cib_transaction_id') ?? $request->input('cib_transaction_id') ?? '');

        if ($invoiceId === '' && $cibTransactionId === '') {
            return response()->json(['message' => 'Missing payment reference.'], 422);
        }

        try {
            $transaction = $this->payments->verifyAndFulfill(
                $invoiceId !== '' ? $invoiceId : null,
                $cibTransactionId !== '' ? $cibTransactionId : null,
                $request->all(),
            );
        } catch (\Throwable $exception) {
            Log::warning('SofizPay return verification failed', [
                'invoice_id' => $invoiceId,
                'cib_transaction_id' => $cibTransactionId,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'status' => $transaction->status->value,
            'invoice_id' => $transaction->invoice_id,
            'order_id' => $transaction->order_id,
            'deposit_id' => $transaction->deposit_id,
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $invoiceId = (string) ($request->input('invoice_id') ?? $request->query('invoice_id') ?? '');
        $cibTransactionId = (string) ($request->input('cib_transaction_id') ?? $request->input('order_number') ?? '');

        if ($invoiceId === '' && $cibTransactionId === '') {
            return response()->json(['ok' => true]);
        }

        try {
            $this->payments->verifyAndFulfill(
                $invoiceId !== '' ? $invoiceId : null,
                $cibTransactionId !== '' ? $cibTransactionId : null,
                $request->all(),
            );
        } catch (\Throwable $exception) {
            Log::warning('SofizPay webhook verification failed', [
                'invoice_id' => $invoiceId,
                'cib_transaction_id' => $cibTransactionId,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
