<?php

use App\Models\Category;
use App\Models\CategoryAttribute;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('local') || config('mail.mailers.smtp.host') !== 'mailpit') {
    throw new RuntimeException('Only local QA with Mailpit is permitted.');
}
$path = base_path('.tools/catalog-browser.json');
$action = $argv[1] ?? '';
if ($action === 'create') {
    if (file_exists($path)) {
        echo "Existing scoped QA fixture retained.\n";
        exit(0);
    }
    $records = [];
    foreach (['desktop', 'mobile'] as $project) {
        $uuid = (string) Str::uuid();
        $password = Str::random(40);
        $user = User::create(['name' => 'QAADMIN '.$uuid, 'email' => 'qa-admin-'.$uuid.'@example.test', 'password' => $password, 'account_type' => 'individual', 'locale' => 'ro']);
        $user->forceFill(['email_verified_at' => now(), 'is_admin' => true])->save();
        $records[$project] = ['id' => $user->id, 'uuid' => $uuid, 'email' => $user->email, 'name' => $user->name, 'password' => $password, 'slug' => 'qa-category-'.$uuid];
    }
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0700, true);
    }
    file_put_contents($path, json_encode($records, JSON_THROW_ON_ERROR));
    chmod($path, 0600);
    echo "Created two isolated browser QA administrators. Credentials remain in ignored local storage.\n";
} elseif ($action === 'clean') {
    if (! file_exists($path)) {
        exit(0);
    }
    $records = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    foreach ($records as $record) {
        if (! Str::isUuid($record['uuid']) || $record['name'] !== 'QAADMIN '.$record['uuid'] || $record['email'] !== 'qa-admin-'.$record['uuid'].'@example.test' || $record['slug'] !== 'qa-category-'.$record['uuid']) {
            throw new RuntimeException('Invalid QA marker.');
        }
        DB::transaction(function () use ($record) {
            $user = User::whereKey($record['id'])->where('name', $record['name'])->where('email', $record['email'])->first();
            if (! $user) {
                return;
            }
            $ids = Category::whereIn('slug', [$record['slug'], $record['slug'].'-child'])->pluck('id');
            CategoryAttribute::whereIn('category_id', $ids)->delete();
            Category::where('slug', $record['slug'].'-child')->delete();
            Category::where('slug', $record['slug'])->delete();
            DB::table('admin_audits')->where('actor_id', $user->id)->delete();
            $user->delete();
        });
    }
    unlink($path);
    echo "Removed only the two recorded QA administrators and their UUID-labelled taxonomy.\n";
} else {
    throw new RuntimeException('Choose create or clean.');
}
