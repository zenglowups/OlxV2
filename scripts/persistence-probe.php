<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Explicit local-only restart/persistence test. Not invoked by normal startup.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! $app->environment('local')) {
    throw new RuntimeException('Persistence probes are restricted to local development.');
}
$action = $argv[1] ?? '';
$statePath = base_path('.tools/persistence-state.json');
$disk = Storage::disk('s3');
$cache = Cache::store('redis');

if ($action === 'create') {
    if (file_exists($statePath)) {
        throw new RuntimeException('A probe exists; verify and clean it before creating another.');
    }
    $id = (string) Str::uuid();
    if (! is_dir(dirname($statePath))) {
        mkdir(dirname($statePath), 0700, true);
    }
    file_put_contents($statePath, json_encode(['id' => $id], JSON_THROW_ON_ERROR));
    DB::table('cache')->insert(['key' => 'persistence:'.$id, 'value' => $id, 'expiration' => time() + 3600]);
    $cache->put('persistence:'.$id, $id, 3600);
    $disk->put('infrastructure/persistence-'.$id.'.txt', $id);
    echo "Created isolated database, Redis and S3 markers. Run STOP then START, then verify.\n";
} elseif (in_array($action, ['verify', 'clean'], true)) {
    $id = json_decode(file_get_contents($statePath), true, 512, JSON_THROW_ON_ERROR)['id'];
    if (! Str::isUuid($id)) {
        throw new RuntimeException('Invalid probe identifier.');
    }
    $key = 'persistence:'.$id;
    $path = 'infrastructure/persistence-'.$id.'.txt';
    if ($action === 'verify') {
        $checks = [
            'PostgreSQL' => DB::table('cache')->where('key', $key)->value('value') === $id,
            'Redis' => $cache->get($key) === $id,
            'S3' => $disk->get($path) === $id,
        ];
        foreach ($checks as $name => $ok) {
            echo $name.': '.($ok ? 'PRESERVED' : 'FAILED')."\n";
        }
        exit(in_array(false, $checks, true) ? 1 : 0);
    }
    DB::table('cache')->where('key', $key)->delete();
    $cache->forget($key);
    $disk->delete($path);
    unlink($statePath);
    echo "Removed only this probe's markers.\n";
} else {
    fwrite(STDERR, "Usage: php scripts/persistence-probe.php create|verify|clean\n");
    exit(1);
}
