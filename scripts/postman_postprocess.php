<?php

declare(strict_types=1);

$source = __DIR__.'/../storage/app/private/scribe/collection.json';
$collectionOut = __DIR__.'/../postman/collections/tocaan-api.postman_collection.json';
$docsOut = __DIR__.'/../docs/postman_collection.json';
$envOut = __DIR__.'/../postman/environments/tocaan.postman_environment.json';

if (! file_exists($source)) {
    fwrite(STDERR, "Scribe collection not found at {$source}. Run scribe:generate first.\n");
    exit(1);
}

$collection = json_decode((string) file_get_contents($source), true, 512, JSON_THROW_ON_ERROR);

$alreadyNested = false;
foreach ($collection['item'] as $group) {
    if (in_array($group['name'] ?? '', ['Admin', 'Member'], true)) {
        $alreadyNested = true;
        break;
    }
}
if ($alreadyNested) {
    fwrite(STDERR, "Input is already nested (Admin/Member folders present). Re-run scribe:generate for a clean source. Aborting to avoid double-wrapping.\n");
    exit(1);
}

$captureScript = static function (string $envKey): array {
    return [
        'listen' => 'test',
        'script' => [
            'type' => 'text/javascript',
            'exec' => [
                'const res = pm.response.json();',
                'if (res && res.access_token) {',
                "    pm.environment.set('{$envKey}', res.access_token);",
                "    console.log('Saved {$envKey}');",
                '}',
            ],
        ],
    ];
};

$bearerAuth = static fn (string $token): array => [
    'type' => 'bearer',
    'bearer' => [['key' => 'token', 'value' => '{{'.$token.'}}', 'type' => 'string']],
];

$loginRequests = [
    'Login as an admin' => 'adminToken',
    'Login as a member' => 'memberToken',
    'Register a member' => 'memberToken',
];

$loginBodies = [
    'Login as an admin' => ['email' => 'admin@tocaan.test', 'password' => 'password'],
];

$adminFolder = ['name' => 'Admin', 'item' => []];
$memberFolder = ['name' => 'Member', 'item' => []];
$rootItems = [];

foreach ($collection['item'] as $group) {
    $name = $group['name'] ?? '';
    $isAdmin = str_starts_with($name, 'Admin');
    $isMember = str_starts_with($name, 'Member');

    if (isset($group['item'])) {
        foreach ($group['item'] as &$request) {
            if (isset($loginRequests[$request['name'] ?? ''])) {
                $request['event'] = array_values(array_filter(
                    $request['event'] ?? [],
                    static fn ($e) => ($e['listen'] ?? '') !== 'test'
                ));
                $request['event'][] = $captureScript($loginRequests[$request['name']]);
            }
            if (isset($loginBodies[$request['name'] ?? '']) && isset($request['request']['body']['raw'])) {
                $request['request']['body']['raw'] = json_encode(
                    $loginBodies[$request['name']],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                );
            }
        }
        unset($request);
    }

    if ($isAdmin) {
        $group['name'] = trim(substr($name, strlen('Admin'))) ?: $name;
        $group['auth'] = $bearerAuth('adminToken');
        $adminFolder['item'][] = $group;
    } elseif ($isMember) {
        $group['name'] = trim(substr($name, strlen('Member'))) ?: $name;
        $group['auth'] = $bearerAuth('memberToken');
        $memberFolder['item'][] = $group;
    } else {
        $rootItems[] = $group;
    }
}

$newItems = [];
if ($adminFolder['item'] !== []) {
    $adminFolder['auth'] = $bearerAuth('adminToken');
    $newItems[] = $adminFolder;
}
if ($memberFolder['item'] !== []) {
    $memberFolder['auth'] = $bearerAuth('memberToken');
    $newItems[] = $memberFolder;
}
$collection['item'] = array_merge($newItems, $rootItems);

$collection['variable'] = [
    ['key' => 'baseUrl', 'value' => 'https://tocaan.test', 'type' => 'string'],
];

$json = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@mkdir(dirname($collectionOut), 0755, true);
@mkdir(dirname($docsOut), 0755, true);
file_put_contents($collectionOut, $json);
file_put_contents($docsOut, $json);

$servedOut = __DIR__.'/../storage/app/private/scribe/collection.postman.json';
file_put_contents($servedOut, $json);

$environment = [
    'id' => '0a4207a4-be83-41f5-ae4c-tocaan000env',
    'name' => 'Tocaan (Local)',
    'values' => [
        ['key' => 'baseUrl', 'value' => 'https://tocaan.test', 'type' => 'default', 'enabled' => true],
        ['key' => 'adminToken', 'value' => '', 'type' => 'secret', 'enabled' => true],
        ['key' => 'memberToken', 'value' => '', 'type' => 'secret', 'enabled' => true],
    ],
    '_postman_variable_scope' => 'environment',
];

@mkdir(dirname($envOut), 0755, true);
file_put_contents($envOut, json_encode($environment, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

fwrite(STDOUT, "Postman post-process complete:\n");
fwrite(STDOUT, "  collection -> {$collectionOut}\n");
fwrite(STDOUT, "  docs copy  -> {$docsOut}\n");
fwrite(STDOUT, "  environment-> {$envOut}\n");
