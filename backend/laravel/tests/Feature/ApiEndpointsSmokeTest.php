<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiEndpointsSmokeTest extends TestCase
{
    public function test_all_api_endpoints_return_non_server_status_for_guest_requests(): void
    {
        $routes = $this->apiRoutes();

        $this->assertNotEmpty($routes, 'No API routes were discovered for smoke testing.');

        foreach ($routes as $route) {
            $method = $this->primaryMethod($route->methods());
            if ($method === null) {
                continue;
            }

            $uri = '/' . ltrim($this->sampleUri((string) $route->uri()), '/');

            $response = $this->json($method, $uri, $this->samplePayload($method));
            $status = $response->getStatusCode();

            $this->assertTrue(
                $status < 500,
                sprintf('%s %s returned %d', $method, $uri, $status)
            );
        }
    }

    private function apiRoutes(): array
    {
        return array_values(array_filter(Route::getRoutes()->getRoutes(), function ($route) {
            $uri = (string) $route->uri();
            return str_starts_with($uri, 'api/');
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
        return in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            ? []
            : [];
    }
}
