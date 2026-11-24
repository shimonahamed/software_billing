<?php

namespace SoftwareBilling\Services;

use SoftwareBilling\Models\BillingConfiguration;
use SoftwareBilling\Models\BillInformation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Contracts\Encryption\DecryptException;
use Throwable;

class BillingApiService
{
    /**
     * Fetch billing information from configured API.
     */
    public function fetch(): array
    {
        $config = BillingConfiguration::find(1);
        if (!$config) {
            return ['success' => false,'message' => 'Billing configuration not found.',];
        }
        if ((int) $config->status !== 1) {
            return ['success' => false,'message' => 'Billing API is disabled.',];
        }
        if (!filter_var($config->api_url, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'message' => 'Invalid API URL.',];
        }
        try {
            $billInfo = BillInformation::query()->orderByDesc('billing_start_date')->first();
            $date = $billInfo?->billing_start_date;
            $fromDate = $date ? $date->toDateString() : '';
            $toDate = $date ? $date->copy()->addMonth()->endOfMonth()->toDateString(): '';
            $url = $config->api_url;
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . http_build_query([
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ]);
            $headers = ['Accept' => 'application/json',];
            if (!empty($config->api_token)) {
                $headers['Authorization'] =
                    'Bearer ' . Crypt::decryptString($config->api_token);
            }
            $response = Http::withHeaders($headers)->timeout(15)->get($url);
            if (!$response->successful()) {
                Log::error('Billing API request failed.', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return ['success' => false,'message' => 'API request failed (' .$response->status() . ').',];
            }
            $payload = $response->json();
            $bills = is_array($payload['data'] ?? null)? $payload['data']: [];
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
            Log::info('Billing API data fetched successfully.', [
                'total_bills' => count($bills),
            ]);
            return [
                'success' => true,
                'message' => 'Billing data fetched successfully.',
                'total_bills' => count($bills),
                'bills' => $bills,
            ];

        } catch (ConnectionException $e) {
            Log::error('Billing API connection failed.', [
                'error' => $e->getMessage(),
            ]);
            return ['success' => false,'message' => 'Unable to connect to the billing API.',];

        } catch (DecryptException $e) {
            Log::error('Billing API token decryption failed.');
            return [ 'success' => false, 'message' => 'Unable to decrypt API token. Please update the configuration.',];

        } catch (QueryException $e) {
            Log::error('Billing API database save failed.', ['error' => $e->getMessage(),]);
            return ['success' => false,'message' => 'Billing data could not be saved.',];
        } catch (Throwable $e) {
            Log::error('Unexpected billing API error.', [ 'error' => $e->getMessage(),]);
            return [ 'success' => false, 'message' => 'An unexpected error occurred.', ];
        }
    }
}
