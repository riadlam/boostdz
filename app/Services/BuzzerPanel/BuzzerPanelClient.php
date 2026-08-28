<?php

namespace App\Services\BuzzerPanel;

use App\Models\Provider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BuzzerPanelClient
{
    public function __construct(private readonly Provider $provider) {}

    public static function fromProvider(Provider $provider): self
    {
        return new self($provider);
    }

    public static function fromConfig(): self
    {
        $provider = Provider::query()->where('slug', config('buzzerpanel.provider_slug'))->first();

        if (! $provider) {
            throw new RuntimeException('BuzzerPanel provider is not configured in the database.');
        }

        return new self($provider);
    }

    /**
     * Catalog list. Prefer services3 (includes cat_id) when configured.
     *
     * @return array<int, array<string, mixed>>
     */
    public function services(): array
    {
        $action = (string) config('buzzerpanel.services_action', 'services3');

        if (! in_array($action, ['services', 'services_1', 'services2', 'services3'], true)) {
            $action = 'services';
        }

        $response = $this->call(['action' => $action]);

        return $this->unwrapList($response);
    }

    /**
     * Place order: action=order, fields service, data (target URL/username), quantity, …
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function placeOrder(array $payload): array
    {
        return $this->call(array_merge(['action' => 'order'], $payload));
    }

    /**
     * Order status: action=status, id=provider order id.
     *
     * @return array<string, mixed>
     */
    public function orderStatus(int|string $orderId): array
    {
        return $this->call([
            'action' => 'status',
            'id' => $orderId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function profile(): array
    {
        return $this->call(['action' => 'profile']);
    }

    /**
     * Request refill: action=refill, id=provider order id.
     *
     * @return array<string, mixed>
     */
    public function refill(int|string $orderId): array
    {
        return $this->call([
            'action' => 'refill',
            'id' => $orderId,
        ]);
    }

    /**
     * Refill status: action=refill_status, id=refill id.
     *
     * @return array<string, mixed>
     */
    public function refillStatus(int|string $refillId): array
    {
        return $this->call([
            'action' => 'refill_status',
            'id' => $refillId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function call(array $payload): array
    {
        $body = array_merge([
            'api_key' => $this->provider->api_key,
            'secret_key' => $this->provider->meta['secret_key'] ?? config('buzzerpanel.secret_key'),
        ], $payload);

        try {
            $response = Http::timeout(120)
                ->acceptJson()
                ->asJson()
                ->post($this->provider->api_url, $body);
        } catch (ConnectionException $exception) {
            throw new BuzzerPanelException('BuzzerPanel request failed: '.$exception->getMessage(), previous: $exception);
        }

        if (! $response->successful()) {
            throw new BuzzerPanelException('BuzzerPanel HTTP error: '.$response->status());
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new BuzzerPanelException('BuzzerPanel returned invalid JSON.');
        }

        if (($json['status'] ?? false) !== true) {
            $message = is_array($json['data'] ?? null)
                ? ($json['data']['msg'] ?? null)
                : null;

            $message ??= $json['msg'] ?? $json['message'] ?? null;

            if (! is_string($message) || trim($message) === '') {
                $message = 'Unknown BuzzerPanel error';
            }

            throw new BuzzerPanelException((string) $message, $json);
        }

        $data = $json['data'] ?? null;

        if (is_array($data)) {
            return $data;
        }

        return ['raw' => $data];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<int, array<string, mixed>>
     */
    protected function unwrapList(array $response): array
    {
        if (array_is_list($response)) {
            return $response;
        }

        foreach (['services', 'data', 'list'] as $key) {
            if (isset($response[$key]) && is_array($response[$key])) {
                return array_is_list($response[$key]) ? $response[$key] : array_values($response[$key]);
            }
        }

        return [];
    }
}
