# stellarsecurity-esim-laravel

Thin Laravel client for the Stellar Simcard API.

The Simcard API exposes:

- `GET  /v1/sim/plans`
- `POST /v1/sim/order`
- `GET  /v1/sim/query/{planId}`

This package just makes it easy to call those endpoints from any Laravel project.

No controllers, no routes, no migrations. You keep your own API; this package just gives you a typed client.

---

## Installation

```bash
composer require stellarsecurity/esim-laravel
```

Laravel will auto-discover the service provider.

Optionally publish the config:

```bash
php artisan vendor:publish --tag=sim-config
```

---

## Configuration (.env)

```env
SIM_API_BASE_URL=https://sim-api.stellar.your-domain.com/api
SIM_API_USERNAME=your-basic-auth-username
SIM_API_PASSWORD=your-basic-auth-password
```

These are the credentials and base URL of the **Simcard API** that actually owns the `/v1/sim/*` routes.

---

## Usage

Inject `SimApiClient` anywhere in your app:

```php
use StellarSecurity\EsimLaravel\Client\SimApiClient;

class SomeService
{
    public function __construct(
        private readonly SimApiClient $simApi,
    ) {}

    public function example(): void
    {
        // Get plans
        $plans = $this->simApi->plans();

        // Create order
        $orderResponse = $this->simApi->order([
            'plan_id'     => 'client-generated-plan-id',
            'packageCode' => 'EU_10GB_30DAYS',
            'account_ref' => 'optional-account-ref',
        ]);

        // Query by plan_id
        $status = $this->simApi->query('client-generated-plan-id');
    }
}
```

Or use it directly in a controller:

```php
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StellarSecurity\EsimLaravel\Client\SimApiClient;

class EsimController
{
    public function __construct(
        private readonly SimApiClient $simApi,
    ) {}

    public function plans(Request $request): JsonResponse
    {
        $plans = $this->simApi->plans($request->all());

        return response()->json($plans);
    }
}
```

---

## What this package is **not**

- It is **not** the Simcard API itself.
- It does **not** define routes or controllers.
- It does **not** define the `simcards` table.

It is a **small HTTP client** so your other projects can talk to the Simcard API's:

- `/v1/sim/plans`
- `/v1/sim/order`
- `/v1/sim/query/{planId}`

using configured base URL + basic auth, without copy/pasting HTTP calls every time.
