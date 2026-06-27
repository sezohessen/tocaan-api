<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotency
{
    private const HEADER = 'Idempotency-Key';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header(self::HEADER);

        if (blank($key)) {
            return $next($request);
        }

        $cacheKey = $this->cacheKey($request, (string) $key);
        $fingerprint = $this->fingerprint($request);

        $claimed = Cache::add($cacheKey, [
            'request_fingerprint' => $fingerprint,
            'response_status' => null,
            'response_body' => null,
        ], $this->ttl());

        if (! $claimed) {
            return $this->handleExisting($cacheKey, (string) $key, $fingerprint);
        }

        $response = $next($request);

        $this->persistResponse($cacheKey, $fingerprint, $response);

        $response->headers->set(self::HEADER, (string) $key);

        return $response;
    }

    private function handleExisting(string $cacheKey, string $key, string $fingerprint): Response
    {
        /** @var array{request_fingerprint: string, response_status: int|null, response_body: array<string, mixed>|null}|null $record */
        $record = Cache::get($cacheKey);

        if ($record === null) {
            return response()->json([
                'message' => __('A request with this idempotency key is still being processed.'),
            ], 409);
        }

        if ($record['request_fingerprint'] !== $fingerprint) {
            return response()->json([
                'message' => __('This idempotency key was already used with different request parameters.'),
            ], 422);
        }

        if ($record['response_status'] === null) {
            return response()->json([
                'message' => __('A request with this idempotency key is still being processed.'),
            ], 409);
        }

        return response()->json($record['response_body'], $record['response_status'])
            ->header(self::HEADER, $key);
    }

    private function persistResponse(string $cacheKey, string $fingerprint, Response $response): void
    {
        if ($response->getStatusCode() >= 500) {
            Cache::forget($cacheKey);

            return;
        }

        Cache::put($cacheKey, [
            'request_fingerprint' => $fingerprint,
            'response_status' => $response->getStatusCode(),
            'response_body' => $response instanceof JsonResponse
                ? $response->getData(true)
                : ['raw' => $response->getContent()],
        ], $this->ttl());
    }

    private function cacheKey(Request $request, string $key): string
    {
        $scope = (string) ($request->route()?->getName() ?: $request->path());

        return 'idempotency:'.$scope.':'.$key;
    }

    private function fingerprint(Request $request): string
    {
        return hash('sha256', json_encode($request->all()) ?: '');
    }

    private function ttl(): \DateTimeInterface
    {
        return now()->addHours((int) config('payments.idempotency_ttl_hours', 24));
    }
}
