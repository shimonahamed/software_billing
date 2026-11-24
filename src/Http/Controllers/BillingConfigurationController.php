<?php

namespace SoftwareBilling\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use SoftwareBilling\Models\BillInformation;
use SoftwareBilling\Models\BillingConfiguration;

class BillingConfigurationController extends Controller
{
    public function show()
    {
        $config = BillingConfiguration::find(1);
        $configuration = [
            'api_url' => $config?->api_url,
            'fetch_interval' => $config?->fetch_interval ?? 60,
            'status' => $config?->status ?? 1,
            'token_configured' => !empty($config?->api_token),
            'last_fetch_at' => $config?->last_fetch_at?->toDateTimeString(),
        ];
        $bills = [];
        $payload = [];
        $fetchError = null;

        if ( $config && (int) $config->status === 1 && filter_var($config->api_url, FILTER_VALIDATE_URL)) {
            $billInfo = BillInformation::orderBy('billing_start_date', 'desc')->first();
            $date = $billInfo?->billing_start_date;
            $fromDate = $date? $date->toDateString(): '';
            $toDate = $date? $date->copy()->addMonth()->endOfMonth()->toDateString(): '';
            $url = $config->api_url;
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . http_build_query([
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ]);

            try {
                $headers = ['Accept' => 'application/json'];

                if (!empty($config->api_token)) {
                    $headers['Authorization'] =
                        'Bearer ' . Crypt::decryptString($config->api_token);
                }
                $response = Http::withHeaders($headers)
                    ->timeout(15)
                    ->get($url);
                if (!$response->successful()) {
                    $fetchError = 'API request failed (' .
                        $response->status() . ').';
                } else {
                    $responsePayload = $response->json();
                    $payload = is_array($responsePayload) ? $responsePayload : [];
                    $bills = is_array($payload['data'] ?? null) ? $payload['data'] : [];

                    DB::transaction(function () use ($bills): void {
                        foreach ($bills as $bill) {
                            if (!is_array($bill) || $bill === []) {
                                continue;
                            }
                            $billId = $bill['bill_id'] ?? null;
                            if ($billId !== null && $billId !== '') {
                                BillInformation::updateOrCreate(
                                    ['bill_id' => $billId],
                                    $bill
                                );
                                continue;
                            }
                            BillInformation::create($bill);
                        }
                    });
                    $config->last_fetch_at = now();
                    $config->save();
                }
            } catch (ConnectionException) {
                $fetchError = 'Unable to connect to the billing API.';
            } catch (DecryptException) {
                $fetchError = 'Unable to decrypt the API token. Please update the configuration.';
            } catch (QueryException) {
                $fetchError = 'Billing data was fetched but could not be saved.';
            }
        }

        $latestBill = BillInformation::query()
            ->orderByDesc('billing_start_date')
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'configuration' => $configuration,
            'bills' => $bills,
            'latest_bill' => $latestBill?->only([
                'id',
                'bill_type',
                'bill_id',
                'bill_month',
                'generate_date',
                'billing_start_date',
                'billing_end_date',
                'amount',
                'paid_amount',
                'due_amount',
                'bill_amount',
                'pre_due_bill_amount',
                'total_due_bill_amount',
                'total_bill_amount',
                'due_date',
                'status',
                'remarks',
            ]),
            'fetch_error' => $fetchError,
        ]);
    }

    public function updateLatestBillStatus(Request $request)
    {
        $validated = $request->validate([
            'status' => ['required', 'integer', 'in:0,2'],
        ]);
        ddA($request);

        $latestBill = BillInformation::query()
            ->orderByDesc('billing_start_date')
            ->orderByDesc('id')
            ->first();

        if (!$latestBill) {
            return response()->json(['message' => 'No bill was found.'], 404);
        }

        $latestBill->status = $validated['status'];
        $latestBill->save();

        return response()->json([
            'message' => 'Bill status updated successfully.',
            'status' => $latestBill->status,
        ]);
    }

//    private function saveBills(array $bills): void
//    {
//        if (empty($bills)) {
//            return;
//        }
//
//        DB::transaction(function () use ($bills): void {
//            foreach ($bills as $bill) {
//                if (empty($bill)) {
//                    continue;
//                }
//
//                BillInformation::create($bill);
//            }
//        });
//    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'api_url' => ['required', 'url', 'max:500'],
            'api_token' => ['nullable', 'string', 'max:10000'],
            'fetch_interval' => ['required', 'integer', 'min:1', 'max:10080'],
            'status' => ['required', 'integer', 'in:0,1'],
        ]);

        $config = BillingConfiguration::find(1) ?? new BillingConfiguration();

        $config->id = 1;
        $config->api_url = $validated['api_url'];
        $config->fetch_interval = $validated['fetch_interval'];
        $config->status = $validated['status'];

        if (!empty($validated['api_token'])) {
            $config->api_token = Crypt::encryptString(
                $validated['api_token']
            );
        }

        $config->save();

        return response()->json([
            'message' => 'Configuration saved successfully.',
            'token_configured' => !empty($config->api_token),
        ]);
    }

}
