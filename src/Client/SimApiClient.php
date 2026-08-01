<?php

namespace StellarSecurity\EsimLaravel\Client;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class SimApiClient
{
    private const USER_LINK_SOURCES = [
        'purchase',
        'manual_claim',
        'account_migration',
        'support',
        'topup',
        'mobile_app',
    ];

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $username,
        private readonly string $password,
        private readonly int $timeoutSeconds = 35,
        private readonly int $connectTimeoutSeconds = 20,
        private readonly string $requestIdHeader = 'X-Request-ID',
    ) {}

    /** Get the currently configured API base URL. */
    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /** Call GET /v1/sim/plans. */
    public function plans(array $filters = [], ?string $requestId = null): array
    {
        return $this->decode(
            $this->http($requestId)
                ->get($this->endpoint('/v1/sim/plans'), $filters)
                ->throw()
        );
    }

    /**
     * Call POST /v1/sim/order.
     *
     * user_id is optional. When omitted, the SIM API keeps the eSIM anonymous.
     */
    public function order(array $payload, ?string $requestId = null): array
    {
        if (array_key_exists('plan_id', $payload)) {
            $payload['plan_id'] = $this->normalizePlanId((string) $payload['plan_id']);
        }

        if (array_key_exists('user_id', $payload) && $payload['user_id'] !== null) {
            $payload['user_id'] = $this->validateUserId((int) $payload['user_id']);
        }

        return $this->decode(
            $this->http($requestId)
                ->post($this->endpoint('/v1/sim/order'), $payload)
                ->throw()
        );
    }

    /** Call POST /v1/sim/query. */
    public function query(string $planId, ?string $requestId = null): array
    {
        return $this->decode(
            $this->http($requestId)
                ->post($this->endpoint('/v1/sim/query'), [
                    'plan_id' => $this->normalizePlanId($planId),
                ])
                ->throw()
        );
    }

    /**
     * Call POST /v1/sim/user and list all eSIMs linked to a verified user.
     *
     * The raw user ID is used only for the authenticated server-to-server
     * request. The SIM API stores a keyed, versioned user reference.
     */
    public function user(int $userId, ?string $requestId = null): array
    {
        return $this->decode(
            $this->http($requestId)
                ->post($this->endpoint('/v1/sim/user'), [
                    'user_id' => $this->validateUserId($userId),
                ])
                ->throw()
        );
    }

    /**
     * Call PATCH /v1/sim/user and assign an existing SIM ID to a user.
     */
    public function patchUser(
        string $planId,
        int $userId,
        string $source = 'mobile_app',
        ?string $requestId = null,
    ): array {
        return $this->decode(
            $this->http($requestId)
                ->patch($this->endpoint('/v1/sim/user'), [
                    'plan_id' => $this->normalizePlanId($planId),
                    'user_id' => $this->validateUserId($userId),
                    'source' => $this->validateSource($source),
                ])
                ->throw()
        );
    }

    /** Call DELETE /v1/sim/user and detach one eSIM from its verified user. */
    public function deleteUser(
        string $planId,
        int $userId,
        ?string $requestId = null,
    ): array {
        return $this->decode(
            $this->http($requestId)
                ->delete($this->endpoint('/v1/sim/user'), [
                    'plan_id' => $this->normalizePlanId($planId),
                    'user_id' => $this->validateUserId($userId),
                ])
                ->throw()
        );
    }

    /** Call DELETE /v1/sim/user/all for account-deletion/privacy workflows. */
    public function deleteAllUser(int $userId, ?string $requestId = null): array
    {
        return $this->decode(
            $this->http($requestId)
                ->delete($this->endpoint('/v1/sim/user/all'), [
                    'user_id' => $this->validateUserId($userId),
                ])
                ->throw()
        );
    }

    /** Descriptive alias for user(). */
    public function listUserSimcards(int $userId, ?string $requestId = null): array
    {
        return $this->user($userId, $requestId);
    }

    /** Descriptive alias for patchUser(). */
    public function assignSimcardToUser(
        string $planId,
        int $userId,
        string $source = 'mobile_app',
        ?string $requestId = null,
    ): array {
        return $this->patchUser($planId, $userId, $source, $requestId);
    }

    /** Descriptive alias for deleteUser(). */
    public function detachSimcardFromUser(
        string $planId,
        int $userId,
        ?string $requestId = null,
    ): array {
        return $this->deleteUser($planId, $userId, $requestId);
    }

    /** Descriptive alias for deleteAllUser(). */
    public function detachAllSimcardsFromUser(
        int $userId,
        ?string $requestId = null,
    ): array {
        return $this->deleteAllUser($userId, $requestId);
    }

    private function ensureConfigured(): void
    {
        if ($this->baseUrl === '' || $this->username === '' || $this->password === '') {
            throw new RuntimeException(
                'Sim API client is not configured. Check sim.base_url, sim.username and sim.password.'
            );
        }
    }

    private function http(?string $requestId = null): PendingRequest
    {
        $this->ensureConfigured();

        $request = Http::acceptJson()
            ->asJson()
            ->timeout(max(1, $this->timeoutSeconds))
            ->connectTimeout(max(1, $this->connectTimeoutSeconds))
            ->withBasicAuth($this->username, $this->password);

        $resolvedRequestId = $this->resolveRequestId($requestId);

        if ($resolvedRequestId !== null && $this->requestIdHeader !== '') {
            $request = $request->withHeader($this->requestIdHeader, $resolvedRequestId);
        }

        return $request;
    }

    private function endpoint(string $path): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
    }

    private function decode(Response $response): array
    {
        $json = $response->json();

        if (! is_array($json)) {
            throw new UnexpectedValueException(sprintf(
                'Sim API returned an invalid JSON response (HTTP %d).',
                $response->status(),
            ));
        }

        return $json;
    }

    private function validateUserId(int $userId): int
    {
        if ($userId < 1) {
            throw new InvalidArgumentException('user_id must be a positive integer.');
        }

        return $userId;
    }

    private function normalizePlanId(string $planId): string
    {
        $normalized = preg_replace('/\s+/', '', trim($planId)) ?? '';

        if (! preg_match('/^\d{16}$/', $normalized)) {
            throw new InvalidArgumentException('plan_id must contain exactly 16 digits.');
        }

        return $normalized;
    }

    private function validateSource(string $source): string
    {
        $source = trim($source);

        if (! in_array($source, self::USER_LINK_SOURCES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid source. Allowed values: %s.',
                implode(', ', self::USER_LINK_SOURCES),
            ));
        }

        return $source;
    }

    private function resolveRequestId(?string $requestId): ?string
    {
        $requestId = trim((string) $requestId);

        if ($requestId !== '') {
            return $requestId;
        }

        if (! function_exists('app') || ! app()->bound('request')) {
            return null;
        }

        $incoming = trim((string) app('request')->header($this->requestIdHeader));

        return $incoming !== '' ? $incoming : null;
    }
}
