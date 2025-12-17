<?php

namespace StellarSecurity\EsimLaravel\Client;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SimApiClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $username,
        private readonly string $password,
    ) {}

    /**
     * Ensure base URL and credentials are configured.
     */
    private function ensureConfigured(): void
    {
        if ($this->baseUrl === '' || $this->username === '' || $this->password === '') {
            throw new RuntimeException('Sim API client is not configured. Check sim.base_url, sim.username and sim.password.');
        }
    }

    /**
     * Shared HTTP client defaults (timeouts + basic auth).
     */
    private function http()
    {
        return Http::timeout(35)
            ->connectTimeout(20)
            ->withBasicAuth($this->username, $this->password);
    }

    /**
     * Call GET /v1/sim/plans
     */
    public function plans(array $filters = []): array
    {
        $this->ensureConfigured();

        $response = $this->http()
            ->get(rtrim($this->baseUrl, '/') . '/v1/sim/plans', $filters)
            ->throw();

        return $response->json();
    }

    /**
     * Call POST /v1/sim/order
     *
     * Example payload:
     *  [
     *      'plan_id'     => 'client-generated-plan-id',
     *      'packageCode' => 'EU_10GB_30DAYS',
     *      'account_ref' => 'optional-account-ref',
     *  ]
     */
    public function order(array $payload): array
    {
        $this->ensureConfigured();

        $response = $this->http()
            ->post(rtrim($this->baseUrl, '/') . '/v1/sim/order', $payload)
            ->throw();

        return $response->json();
    }

    /**
     * Call POST /v1/sim/query
     *
     * Payload:
     *  [
     *      'plan_id' => 'client-generated-plan-id',
     *  ]
     */
    public function query(string $planId): array
    {
        $this->ensureConfigured();

        $response = $this->http()
            ->post(rtrim($this->baseUrl, '/') . '/v1/sim/query', [
                'plan_id' => $planId,
            ])
            ->throw();

        return $response->json();
    }
}
