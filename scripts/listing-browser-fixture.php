<?php

use App\Models\Listing;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('local') || config('mail.mailers.smtp.host') !== 'mailpit') {
    throw new RuntimeException('Local Mailpit QA only.');
}
$path = base_path('.tools/listing-browser.json');
$action = $argv[1] ?? '';
if ($action === 'create') {
    if (file_exists($path)) {
        echo "Existing scoped listing QA fixture retained.\n";
        exit(0);
    }
    $records = [];
    foreach (['desktop', 'mobile'] as $project) {
        $uuid = (string) Str::uuid();
        $password = Str::random(40);
        $user = User::create(['name' => 'QALISTING '.$uuid, 'email' => 'qa-listing-'.$uuid.'@example.test', 'password' => $password, 'locale' => 'ro']);
        $user->forceFill(['email_verified_at' => now()])->save();
        $records[$project] = ['id' => $user->id, 'uuid' => $uuid, 'name' => $user->name, 'email' => $user->email, 'password' => $password];
    }
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0700, true);
    }
    file_put_contents($path, json_encode($records, JSON_THROW_ON_ERROR));
    chmod($path, 0600);
    echo "Created two isolated listing QA sellers. Credentials stay in ignored local storage.\n";
} elseif ($action === 'clean') {
    if (! file_exists($path)) {
        exit(0);
    }
    foreach (json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR) as $record) {
        if (! Str::isUuid($record['uuid']) || $record['name'] !== 'QALISTING '.$record['uuid'] || $record['email'] !== 'qa-listing-'.$record['uuid'].'@example.test') {
            throw new RuntimeException('Invalid QA marker.');
        }
        DB::transaction(function () use ($record) {
            $user = User::whereKey($record['id'])->where('name', $record['name'])->where('email', $record['email'])->first();
            if (! $user) {
                return;
            }
            Listing::where('user_id', $user->id)->where('status', 'draft')->delete();
            $user->delete();
        });
    }
    unlink($path);
    echo "Removed only the recorded listing QA sellers and their private drafts.\n";
} else {
    throw new RuntimeException('Choose create or clean.');
}
