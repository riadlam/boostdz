<?php

namespace App\Services\SofizPay;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SofizPayClient
{
    public function enabled(): bool
    {
        return (bool) config('sofizpay.enabled')
            && filled(config('sofizpay.merchant_account'));
    }

    /**
     * @param  array<string, scalar|null>  $params
     * @return array<string, mixed>
     */
    public function createTransaction(array $params): array
    {
        $query = array_merge([
            'account' => config('sofizpay.merchant_account'),
            'redirect' => config('sofizpay.redirect', 'no'),
            'keep_return_url' => config('sofizpay.keep_return_url', 'True'),
        ], $params);

        return $this->get($this->createPath(), $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function checkTransaction(string $orderNumber): array
    {
        return $this->get($this->checkPath(), [
            'order_number' => $orderNumber,
        ]);
    }

    protected function createPath(): string
    {
        $prefix = config('sofizpay.sandbox') ? '/sandbox' : '';

        return $prefix.'/make-cib-transaction/';
    }

    protected function checkPath(): string
    {
        $prefix = config('sofizpay.sandbox') ? '/sandbox' : '';

        return $prefix.'/cib-transaction-check/';
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    protected function get(string $path, array $query): array
    {
        $url = rtrim((string) config('sofizpay.base_url'), '/').$path;

        try {
            $response = Http::timeout((int) config('sofizpay.timeout', 30))
                ->acceptJson()
                ->get($url, $query);
        } catch (ConnectionException $exception) {
            throw new SofizPayException('SofizPay request failed: '.$exception->getMessage(), previous: $exception);
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new SofizPayException('SofizPay returned invalid JSON.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        if (! $response->successful()) {
            $message = is_string($json['message'] ?? null)
                ? $json['message']
                : 'SofizPay HTTP error: '.$response->status();

            throw new SofizPayException($message, $json);
        }

        if (($json['status'] ?? null) === 'error') {
            throw new SofizPayException(
                is_string($json['message'] ?? null) ? $json['message'] : 'SofizPay transaction error.',
                $json,
            );
        }

        return $json;
    }
}
