<?php

use App\Models\SellerProfile;
use App\Models\User;
use App\Services\Media\FileCleanup;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Removes only UUID-labelled accounts created by tests/Browser/auth.spec.js.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('local')) {
    throw new RuntimeException('Browser cleanup is restricted to local development.');
}
$path = base_path('.tools/qa-auth-users.jsonl');
if (! file_exists($path)) {
    echo "No browser QA accounts to clean.\n";
    exit(0);
}
foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $record = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
    if (! preg_match('/^qa-auth-([a-f0-9-]{36})@example\\.test$/D', $record['email'], $match)
        || ! Str::isUuid($match[1])
        || $record['name'] !== 'QA '.$match[1]) {
        throw new RuntimeException('Invalid QA account marker; no broad deletion is permitted.');
    }
    DB::transaction(function () use ($record) {
        $user = User::where('email', $record['email'])->where('name', $record['name'])->first();
        if ($user) {
            DB::table('password_reset_tokens')->where('email', $record['email'])->delete();
            $profile = SellerProfile::where('user_id', $user->id)->first();
            if ($profile?->avatar_path) {
                $cleanup = app(FileCleanup::class);
                $cleanup->remember($profile->avatar_path);
            }
            $user->delete();
        }
    });
}
Artisan::call('media:cleanup');
unlink($path);
echo "Cleaned only the explicitly recorded browser QA accounts.\n";
