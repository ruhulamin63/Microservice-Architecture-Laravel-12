<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GatewayController extends Controller
{
    public function __construct()
    {
        $this->services = config('services.microservices');
    }

    /**
     * Proxy authentication requests to auth service
     */
    public function auth(Request $request, $endpoint = null)
    {
        // Extract endpoint from path if not provided
        // if (!$endpoint) {
        //     $path = $request->path();
        //     $endpoint = str_replace('api/auth/', '', $path);
        // }
        return $this->proxyRequest($request, $this->services['auth'], $endpoint);
    }

    /**
     * Proxy user service requests
     */
    public function users(Request $request, $endpoint = null)
    {
        return $this->proxyRequest($request, $this->services['users'], 'api/users/' . $endpoint);
    }

    /**
     * Proxy order service requests
     */
    public function orders(Request $request, $endpoint = null)
    {
        return $this->proxyRequest($request, $this->services['orders'], 'api/orders/' . $endpoint);
    }

    /**
     * Health check for all services
     */
    public function health(Request $request)
    {
        $health = [];

        foreach ($this->services as $name => $url) {
            try {
                $response = Http::timeout(3)->get($url . '/up');
                $health[$name] = [
                    'status' => $response->successful() ? 'healthy' : 'unhealthy',
                    'url' => $url
                ];
            } catch (\Exception $e) {
                $health[$name] = [
                    'status' => 'unhealthy',
                    'url' => $url,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'gateway' => 'healthy',
            'services' => $health,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Generic proxy method for all HTTP methods
     */
    private function proxyRequest(Request $request, $serviceUrl, $endpoint)
    {
        try {
            // Build the target URL
            $targetUrl = $serviceUrl . '/' . $endpoint;
            
            // Remove any trailing slash from endpoint if it exists
            $targetUrl = rtrim($targetUrl, '/');
            // dd($targetUrl);

            // Prepare headers
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            // Forward authorization header if present
            if ($request->hasHeader('Authorization')) {
                $headers['Authorization'] = $request->header('Authorization');
            }

            // Prepare HTTP client
            $httpClient = Http::withHeaders($headers)->timeout(10);

            // Get request data
            $data = $request->all();

            // Make the request based on HTTP method
            switch ($request->method()) {
                case 'GET':
                    $response = $httpClient->get($targetUrl, $data);
                    break;
                case 'POST':
                    $response = $httpClient->post($targetUrl, $data);
                    break;
                case 'PUT':
                    $response = $httpClient->put($targetUrl, $data);
                    break;
                case 'DELETE':
                    $response = $httpClient->delete($targetUrl, $data);
                    break;
                default:
                    return response()->json([
                        'error' => 'Method not allowed'
                    ], 405);
            }

            // Return the response with the same status code
            return response()->json(
                $response->json(),
                $response->status()
            );

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Service unavailable',
                'message' => $e->getMessage(),
                'service' => $serviceUrl
            ], 503);
        }
    }
}
