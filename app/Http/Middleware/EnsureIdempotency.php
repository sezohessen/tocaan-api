<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $scope = (string) ($request->route()?->getName() ?: $request->path());
        $fingerprint = $this->fingerprint($request);

        $record = $this->claim((string) $key, $scope, $fingerprint);

        if ($record->wasRecentlyCreated === false) {
            return $this->handleExisting($record, $fingerprint);
        }

        $response = $next($request);

        $this->persistResponse($record, $response);

        $response->headers->set(self::HEADER, (string) $key);

        return $response;
    }

    private function claim(string $key, string $scope, string $fingerprint): IdempotencyKey
    {
        try {
            $record = IdempotencyKey::create([
                'key' => $key,
                'scope' => $scope,
                'request_fingerprint' => $fingerprint,
            ]);
            $record->wasRecentlyCreated = true;

            return $record;
        } catch (QueryException) {
            $record = IdempotencyKey::query()->where('scope', $scope)->where('key', $key)->firstOrFail();
            $record->wasRecentlyCreated = false;

            return $record;
        }
    }

    private function handleExisting(IdempotencyKey $record, string $fingerprint): Response
    {
        if ($record->request_fingerprint !== $fingerprint) {
            return response()->json([
                'message' => __('This idempotency key was already used with different request parameters.'),
            ], 422);
        }

        if (! $record->isCompleted()) {
            return response()->json([
                'message' => __('A request with this idempotency key is still being processed.'),
            ], 409);
        }

        return response()->json($record->response_body, (int) $record->response_status)
            ->header(self::HEADER, $record->key);
    }

    private function persistResponse(IdempotencyKey $record, Response $response): void
    {
        if ($response->getStatusCode() >= 500) {
            $record->delete();

            return;
        }

        $record->update([
            'response_status' => $response->getStatusCode(),
            'response_body' => $response instanceof JsonResponse
                ? $response->getData(true)
                : ['raw' => $response->getContent()],
        ]);
    }

    private function fingerprint(Request $request): string
    {
        return hash('sha256', json_encode($request->all()) ?: '');
    }
}
