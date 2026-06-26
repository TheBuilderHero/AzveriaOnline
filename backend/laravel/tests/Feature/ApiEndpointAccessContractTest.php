<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiEndpointAccessContractTest extends TestCase
{
    public function test_every_auth_protected_endpoint_requires_authentication(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        $routes = $this->routesWithMiddleware('auth:sanctum');
        $this->assertNotEmpty($routes, 'No auth-protected API routes found.');

        foreach ($routes as $route) {
            $method = $this->primaryMethod($route->methods());
            if ($method === null) {
                continue;
            }

            $uri = '/' . ltrim($this->sampleUri((string) $route->uri()), '/');
            $response = $this->json($method, $uri, $this->samplePayload($method));

            $this->assertSame(
                401,
                $response->getStatusCode(),
                sprintf('%s %s should require authentication', $method, $uri)
            );
        }
    }

    public function test_every_admin_endpoint_forbids_non_admin_players(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        $routes = $this->routesWithMiddleware('role:admin');
        $this->assertNotEmpty($routes, 'No admin API routes found.');

        Sanctum::actingAs($this->makeUser(1001, 'player'));

        foreach ($routes as $route) {
            $method = $this->primaryMethod($route->methods());
            if ($method === null) {
                continue;
            }

            $uri = '/' . ltrim($this->sampleUri((string) $route->uri()), '/');
            $response = $this->json($method, $uri, $this->samplePayload($method));

            $this->assertSame(
                403,
                $response->getStatusCode(),
                sprintf('%s %s should forbid non-admin players', $method, $uri)
            );
        }
    }

    public function test_public_auth_endpoints_validate_input_without_server_errors(): void
    {
        $register = $this->postJson('/api/auth/register', []);
        $login = $this->postJson('/api/auth/login', []);

        $this->assertTrue(
            in_array($register->getStatusCode(), [422, 429], true),
            'POST /api/auth/register should reject invalid payload without 5xx.'
        );
        $this->assertTrue(
            in_array($login->getStatusCode(), [422, 429], true),
            'POST /api/auth/login should reject invalid payload without 5xx.'
        );
    }

    private function routesWithMiddleware(string $needle): array
    {
        return array_values(array_filter(Route::getRoutes()->getRoutes(), function ($route) use ($needle) {
            $uri = (string) $route->uri();
            if (!str_starts_with($uri, 'api/')) {
                return false;
            }

            return in_array($needle, $route->gatherMiddleware(), true);
        }));
    }

    private function primaryMethod(array $methods): ?string
    {
        foreach ($methods as $method) {
            if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                continue;
            }
            return $method;
        }

        return null;
    }

    private function sampleUri(string $uri): string
    {
        return (string) preg_replace_callback('/\{[^}]+\}/', function ($match) {
            $token = strtolower(trim((string) $match[0], '{}?'));
            if (str_contains($token, 'code') || str_contains($token, 'type')) {
                return 'sample';
            }
            return '1';
        }, $uri);
    }

    private function samplePayload(string $method): array
    {
        return in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'], true) ? [] : [];
    }

    private function makeUser(int $id, string $role): User
    {
        $user = new User([
            'name' => $role . '-' . $id,
            'email' => $role . $id . '@example.test',
            'password' => 'password123',
            'role' => $role,
        ]);
        $user->id = $id;

        return $user;
    }
}
