# stellarsecurity/esim-laravel

Thin Laravel client for the Stellar Simcard API.

The package exposes a typed `SimApiClient` for the existing project-style routes:

```text
GET     /v1/sim/plans
POST    /v1/sim/order
POST    /v1/sim/query
POST    /v1/sim/user
PATCH   /v1/sim/user
DELETE  /v1/sim/user
DELETE  /v1/sim/user/all
```

It defines no controllers, application routes, models, migrations, or database tables.

## Installation

```bash
composer require stellarsecurity/esim-laravel
```

Laravel auto-discovers the service provider.

Optionally publish its configuration:

```bash
php artisan vendor:publish --tag=sim-config
```

## Configuration

```env
SIM_API_BASE_URL=https://your-sim-api.example.com/api
SIM_API_USERNAME=your-basic-auth-username
SIM_API_PASSWORD=your-basic-auth-password

SIM_API_TIMEOUT=35
SIM_API_CONNECT_TIMEOUT=20
SIM_API_REQUEST_ID_HEADER=X-Request-ID
```

In Azure App Service, store the credential values as application settings or Key Vault references. Do not put SIM API credentials in a mobile or browser application.

## Usage

```php
use StellarSecurity\EsimLaravel\Client\SimApiClient;

final class EsimService
{
    public function __construct(
        private readonly SimApiClient $simApi,
    ) {}
}
```

### Plans, order and query

```php
$plans = $this->simApi->plans([
    'locationCode' => 'FR',
]);

// user_id is optional. Omitting it creates an anonymous eSIM.
$order = $this->simApi->order([
    'plan_id' => '1234 1234 1234 1234',
    'packageCode' => 'FR_10GB_30DAYS',
    'user_id' => 7345,
]);

$status = $this->simApi->query('1234 1234 1234 1234');
```

SIM IDs are normalized to an unspaced 16-digit value before transmission.

## User ownership

These methods are intended for trusted server-side UI APIs. The UI API must first resolve the canonical Stellar user ID from the authenticated bearer token. A mobile app must never be allowed to choose a trusted `user_id` itself.

### List a user's eSIMs

Project-style method:

```php
$response = $this->simApi->user($userId);
```

Descriptive alias:

```php
$response = $this->simApi->listUserSimcards($userId);
```

Calls:

```text
POST /v1/sim/user
```

### Assign an existing SIM ID

Project-style method:

```php
$response = $this->simApi->patchUser(
    planId: '1234 1234 1234 1234',
    userId: $userId,
    source: 'mobile_app',
);
```

Descriptive alias:

```php
$response = $this->simApi->assignSimcardToUser(
    planId: '1234 1234 1234 1234',
    userId: $userId,
);
```

Calls:

```text
PATCH /v1/sim/user
```

Allowed source values:

```text
purchase
manual_claim
account_migration
support
topup
mobile_app
```

The SIM API stores a keyed, versioned user reference rather than the raw user ID.

### Detach one eSIM

```php
$response = $this->simApi->deleteUser(
    planId: '1234 1234 1234 1234',
    userId: $userId,
);

// Alias:
$response = $this->simApi->detachSimcardFromUser($planId, $userId);
```

Calls:

```text
DELETE /v1/sim/user
```

### Detach all eSIMs for account deletion

```php
$response = $this->simApi->deleteAllUser($userId);

// Alias:
$response = $this->simApi->detachAllSimcardsFromUser($userId);
```

Calls:

```text
DELETE /v1/sim/user/all
```

## Request IDs

Every method accepts an optional final `$requestId` argument:

```php
$response = $this->simApi->user(
    userId: $userId,
    requestId: $request->header('X-Request-ID'),
);
```

When omitted inside an HTTP request, the client automatically forwards the incoming configured request-ID header when present.

## Validation

The client rejects invalid values before making a request:

- `user_id` must be greater than zero.
- `plan_id` must contain exactly 16 digits after spaces are removed.
- Ownership source must be one of the SIM API's supported values.

HTTP failures are raised through Laravel's normal `RequestException`. A non-JSON success response raises `UnexpectedValueException`.

## Security boundary

Correct architecture:

```text
Mobile app
    -> authenticated Mobile UI API
    -> stellarsecurity/esim-laravel
    -> Stellar Simcard API
```

Do not install or configure this package inside a distributed client application. Its Basic Auth credentials are server secrets.

## Testing

```bash
composer test
```
