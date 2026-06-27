<?php

declare(strict_types=1);

$root = __DIR__ . '/..';
$collectionFile = $root . '/postman/collections/tocaan-api.postman_collection.json';
$envFile = $root . '/postman/environments/tocaan.postman_environment.json';
$cacheFile = $root . '/.postman/push-cache.json';

$readEnv = static function (string $key) use ($root): ?string {
    $env = getenv($key);
    if (is_string($env) && $env !== '') {
        return $env;
    }
    $path = $root . '/.env';
    if (! file_exists($path)) {
        return null;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        if (trim($k) === $key) {
            return trim($v, " \t\"'");
        }
    }

    return null;
};

$apiKey = $readEnv('POSTMAN_API_KEY');
if ($apiKey === null || $apiKey === '') {
    fwrite(STDOUT, "POSTMAN_API_KEY not set in .env — skipping Postman push.\n");
    exit(0);
}

$workspaceId = $readEnv('POSTMAN_WORKSPACE_ID');

$request = static function (string $method, string $url, string $apiKey, ?array $body = null): array {
    $ch = curl_init($url);
    $headers = ['X-API-Key: ' . $apiKey, 'Content-Type: application/json'];
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($response === false) {
        return ['status' => 0, 'body' => [], 'error' => $error];
    }

    return ['status' => $status, 'body' => json_decode((string) $response, true) ?? [], 'error' => $error];
};

$cache = file_exists($cacheFile) ? (json_decode((string) file_get_contents($cacheFile), true) ?: []) : [];

$pushCollection = static function () use ($request, $apiKey, $workspaceId, $collectionFile, &$cache, $cacheFile): void {
    $collection = json_decode((string) file_get_contents($collectionFile), true, 512, JSON_THROW_ON_ERROR);
    $name = $collection['info']['name'] ?? 'Tocaan API Documentation';

    $uid = $cache['collectionUid'] ?? null;
    if ($uid === null) {
        $list = $request('GET', 'https://api.getpostman.com/collections', $apiKey);
        foreach (($list['body']['collections'] ?? []) as $c) {
            if (($c['name'] ?? '') === $name) {
                $uid = $c['uid'];
                break;
            }
        }
    }

    if ($uid !== null) {
        $result = $request('PUT', "https://api.getpostman.com/collections/{$uid}", $apiKey, ['collection' => $collection]);
        if ($result['status'] === 200) {
            fwrite(STDOUT, "Collection updated in Postman (uid: {$uid}).\n");
            $cache['collectionUid'] = $uid;
            file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));

            return;
        }
        fwrite(STDERR, "Update failed ({$result['status']}), creating fresh. " . json_encode($result['body']) . "\n");
    }

    $url = 'https://api.getpostman.com/collections';
    if ($workspaceId) {
        $url .= '?workspace=' . $workspaceId;
    }
    $result = $request('POST', $url, $apiKey, ['collection' => $collection]);
    if ($result['status'] === 200 || $result['status'] === 201) {
        $cache['collectionUid'] = $result['body']['collection']['uid'] ?? null;
        file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
        fwrite(STDOUT, "Collection created in Postman (uid: {$cache['collectionUid']}).\n");

        return;
    }
    fwrite(STDERR, "Collection push failed ({$result['status']}): " . json_encode($result['body']) . "\n");
    exit(1);
};

$pushEnvironment = static function () use ($request, $apiKey, $workspaceId, $envFile, &$cache, $cacheFile): void {
    if (! file_exists($envFile)) {
        return;
    }
    $environment = json_decode((string) file_get_contents($envFile), true, 512, JSON_THROW_ON_ERROR);
    $name = $environment['name'] ?? 'Tocaan (Local)';
    unset($environment['id'], $environment['_postman_variable_scope']);

    $uid = $cache['environmentUid'] ?? null;
    if ($uid === null) {
        $list = $request('GET', 'https://api.getpostman.com/environments', $apiKey);
        foreach (($list['body']['environments'] ?? []) as $e) {
            if (($e['name'] ?? '') === $name) {
                $uid = $e['uid'];
                break;
            }
        }
    }

    if ($uid !== null) {
        $result = $request('PUT', "https://api.getpostman.com/environments/{$uid}", $apiKey, ['environment' => $environment]);
        if ($result['status'] === 200) {
            $cache['environmentUid'] = $uid;
            file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
            fwrite(STDOUT, "Environment updated in Postman (uid: {$uid}).\n");

            return;
        }
    }

    $url = 'https://api.getpostman.com/environments';
    if ($workspaceId) {
        $url .= '?workspace=' . $workspaceId;
    }
    $result = $request('POST', $url, $apiKey, ['environment' => $environment]);
    if ($result['status'] === 200 || $result['status'] === 201) {
        $cache['environmentUid'] = $result['body']['environment']['uid'] ?? null;
        file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
        fwrite(STDOUT, "Environment created in Postman (uid: {$cache['environmentUid']}).\n");
    } else {
        fwrite(STDERR, "Environment push failed ({$result['status']}): " . json_encode($result['body']) . "\n");
    }
};

$pushCollection();
$pushEnvironment();
