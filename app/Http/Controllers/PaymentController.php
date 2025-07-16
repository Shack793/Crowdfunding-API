<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * PaymentController handles FalconPay wallet debit and transaction status checks.
 *
 * Endpoints:
 *   POST /api/v1/payments/debit-wallet    - Debit a user's wallet via FalconPay
 *   POST /api/v1/payments/check-status    - Check the status of a FalconPay transaction
 */
class PaymentController extends Controller
{
    /**
     * Format MSISDN to remove leading 0 or country code.
     */
    private function formatMsisdn($msisdn)
    {
        $msisdn = preg_replace('/^0/', '', $msisdn); // Remove leading 0
        $msisdn = preg_replace('/^233/', '', $msisdn); // Remove country code if present
        return $msisdn;
    }

    /**
     * Detect network based on MSISDN prefix.
     */
    private function detectNetwork($msisdn)
    {
        $prefix = substr($msisdn, 0, 3);
        if (in_array($prefix, ['024','025','053','054','055','059'])) {
            return 'MTN';
        } elseif (in_array($prefix, ['020','050'])) {
            return 'VODAFONE';
        } elseif (in_array($prefix, ['027','057','026'])) {
            return 'AIRTELTIGO';
        }
        return 'UNKNOWN';
    }

    /**
     * Debit a user's wallet using FalconPay API.
     *
     * Validates the request, calls FalconPay debit API, and returns the response.
     * Optionally, you can save the FalconPay transactionId to your database here.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function debitWallet(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer' => 'required|string',
                'msisdn' => 'required|string',
                'amount' => 'required|numeric|min:0.01',
                'network' => 'sometimes|string',
                'narration' => 'required|string',
            ]);

            $apiKey = env('FALCONPAY_API_KEY', '');
            $apiSecret = env('FALCONPAY_API_SECRET', '');
            $baseUrl = env('FALCONPAY_BASE_URL', 'https://api.falconpayglobal.com/api/v1');

            $cleanMsisdn = $this->formatMsisdn($validated['msisdn']);
            $network = $validated['network'] ?? $this->detectNetwork($cleanMsisdn);

            $payload = [
                'customer'  => $validated['customer'],
                'msisdn'    => $cleanMsisdn,
                'amount'    => $validated['amount'],
                'network'   => $network,
                'narration' => $validated['narration'],
                'apiKey'    => $apiKey,
                'apiSecret' => $apiSecret
            ];

            // Log the payload for debugging
            \Log::channel('single')->info('FalconPay debitWallet payload', $payload);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($baseUrl . '/momo/debit/wallet', $payload);

            // Log the response for debugging
            \Log::channel('single')->info('FalconPay debitWallet response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $data = $response->json();
            $transactionId = $data['transactionId'] ?? null;
            if ($transactionId && $request->has('contribution_id')) {
                \App\Models\Contribution::where('id', $request->input('contribution_id'))
                    ->update(['transaction_id' => $transactionId, 'transaction_status' => 'pending']);
            }

            return response()->json($data, $response->status());
        } catch (\Exception $e) {
            // Log the error with stack trace
            \Log::channel('single')->error('FalconPay debitWallet error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            // Optionally cache the error for later review
            \Cache::put('falconpay_debitwallet_error_' . now()->timestamp, [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ], now()->addHours(1));
            return response()->json([
                'error' => 'FalconPay debitWallet error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check the status of a FalconPay transaction.
     *
     * Validates the request, calls FalconPay status API, and returns the transaction status.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'refNo' => 'required|string',
            ]);

            $apiKey = env('FALCONPAY_API_KEY', '');
            $apiSecret = env('FALCONPAY_API_SECRET', '');
            $baseUrl = env('FALCONPAY_BASE_URL', 'https://api.falconpayglobal.com/api/v1');

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/check/transaction/refNo', [
                'refNo'     => $validated['refNo'],
                'apiKey'    => $apiKey,
                'apiSecret' => $apiSecret
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'FalconPay checkStatus error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
