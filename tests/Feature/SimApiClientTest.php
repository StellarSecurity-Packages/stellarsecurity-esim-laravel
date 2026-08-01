<?php

namespace StellarSecurity\EsimLaravel\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use StellarSecurity\EsimLaravel\Client\SimApiClient;
use StellarSecurity\EsimLaravel\Tests\TestCase;

final class SimApiClientTest extends TestCase
{
    private SimApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->app->make(SimApiClient::class);
    }

    public function test_lists_user_simcards_using_project_route_style(): void
    {
        Http::fake([
            'https://sim.example.test/api/v1/sim/user' => Http::response([
                'response_code' => 200,
                'data' => [],
            ]),
        ]);

        $response = $this->client->user(7345, 'request-123');

        self::assertSame(200, $response['response_code']);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://sim.example.test/api/v1/sim/user'
                && $request['user_id'] === 7345
                && $request->hasHeader('X-Request-ID', 'request-123');
        });
    }

    public function test_assigns_a_formatted_sim_id_using_patch(): void
    {
        Http::fake([
            'https://sim.example.test/api/v1/sim/user' => Http::response([
                'response_code' => 200,
                'data' => ['status' => 'assigned'],
            ]),
        ]);

        $response = $this->client->patchUser(
            '1234 1234 1234 1234',
            7345,
            'mobile_app',
        );

        self::assertSame('assigned', $response['data']['status']);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'PATCH'
                && $request['plan_id'] === '1234123412341234'
                && $request['user_id'] === 7345
                && $request['source'] === 'mobile_app';
        });
    }

    public function test_detaches_one_simcard_using_delete_request_body(): void
    {
        Http::fake([
            'https://sim.example.test/api/v1/sim/user' => Http::response([
                'response_code' => 200,
                'data' => ['status' => 'detached'],
            ]),
        ]);

        $this->client->deleteUser('1234123412341234', 7345);

        Http::assertSent(fn (Request $request): bool =>
            $request->method() === 'DELETE'
            && $request->url() === 'https://sim.example.test/api/v1/sim/user'
            && $request['plan_id'] === '1234123412341234'
            && $request['user_id'] === 7345
        );
    }

    public function test_detaches_all_simcards_for_a_user(): void
    {
        Http::fake([
            'https://sim.example.test/api/v1/sim/user/all' => Http::response([
                'response_code' => 200,
                'data' => ['detached_count' => 3],
            ]),
        ]);

        $response = $this->client->deleteAllUser(7345);

        self::assertSame(3, $response['data']['detached_count']);

        Http::assertSent(fn (Request $request): bool =>
            $request->method() === 'DELETE'
            && $request->url() === 'https://sim.example.test/api/v1/sim/user/all'
            && $request['user_id'] === 7345
        );
    }

    public function test_descriptive_aliases_use_the_same_routes(): void
    {
        Http::fake([
            '*' => Http::response(['response_code' => 200, 'data' => []]),
        ]);

        $this->client->listUserSimcards(7345);
        $this->client->assignSimcardToUser('1234123412341234', 7345);
        $this->client->detachSimcardFromUser('1234123412341234', 7345);
        $this->client->detachAllSimcardsFromUser(7345);

        Http::assertSentCount(4);
    }

    public function test_rejects_invalid_user_ids_without_sending_a_request(): void
    {
        Http::fake();

        $this->expectException(InvalidArgumentException::class);
        $this->client->user(0);
    }

    public function test_rejects_invalid_sim_ids_without_sending_a_request(): void
    {
        Http::fake();

        $this->expectException(InvalidArgumentException::class);
        $this->client->patchUser('1234', 7345);
    }

    public function test_rejects_unknown_ownership_sources(): void
    {
        Http::fake();

        $this->expectException(InvalidArgumentException::class);
        $this->client->patchUser('1234123412341234', 7345, 'untrusted_source');
    }
}
