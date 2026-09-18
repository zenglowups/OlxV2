<?php

use App\Models\Listing;
use App\Models\ListingPhoto;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\Media\FileCleanup;
use App\Services\Media\ListingPhotos;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
    $records = DB::transaction(function () {
        $records = [];
        foreach (['desktop', 'mobile'] as $project) {
            $uuid = (string) Str::uuid();
            $password = Str::random(40);
            $user = User::create(['name' => 'QALISTING '.$uuid, 'email' => 'qa-listing-'.$uuid.'@example.test', 'password' => $password, 'locale' => 'ro']);
            $user->forceFill(['email_verified_at' => now()])->save();
            SellerProfile::create(['user_id' => $user->id, 'username' => 'qa-'.substr(str_replace('-', '', $uuid), 0, 24), 'is_public' => true, 'description' => 'Profil izolat pentru verificarea locală a anunțurilor.', 'location_label' => 'Cluj-Napoca', 'contact_method' => 'email']);
            $records[$project] = ['id' => $user->id, 'uuid' => $uuid, 'name' => $user->name, 'email' => $user->email, 'password' => $password];
        }

        return $records;
    });
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0700, true);
    }
    file_put_contents($path, json_encode($records, JSON_THROW_ON_ERROR));
    chmod($path, 0600);
    echo "Created two isolated listing QA sellers. Credentials stay in ignored local storage.\n";
} elseif ($action === 'audit-photos') {
    $disk = Storage::disk('s3');
    $checked = 0;
    foreach (json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR) as $record) {
        if (! Str::isUuid($record['uuid']) || $record['name'] !== 'QALISTING '.$record['uuid'] || $record['email'] !== 'qa-listing-'.$record['uuid'].'@example.test') {
            throw new RuntimeException('Invalid QA marker.');
        }
        $user = User::whereKey($record['id'])->where('name', $record['name'])->where('email', $record['email'])->firstOrFail();
        foreach (ListingPhoto::whereIn('listing_id', Listing::where('user_id', $user->id)->select('id'))->get() as $photo) {
            if ($photo->status !== 'ready' || $disk->exists($photo->sourcePath())) {
                throw new RuntimeException('QA photo is incomplete or retains its original.');
            }
            foreach (array_keys(ListingPhoto::VARIANTS) as $variant) {
                $object = $photo->variantPath($variant);
                if ($disk->getVisibility($object) !== 'private') {
                    throw new RuntimeException('QA image ACL is not private.');
                }
                $url = rtrim(config('filesystems.disks.s3.endpoint'), '/').'/'.rawurlencode(config('filesystems.disks.s3.bucket')).'/'.$object;
                if (Http::timeout(5)->head($url)->status() !== 403) {
                    throw new RuntimeException('Anonymous S3 access was not denied.');
                }
                $checked++;
            }
        }
    }
    if (! $checked) {
        throw new RuntimeException('No QA photos available to audit.');
    }
    echo "Verified {$checked} private WebP objects, denied anonymous access and retired originals.\n";
} elseif ($action === 'clean') {
    if (! file_exists($path)) {
        exit(0);
    }
    foreach (json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR) as $record) {
        if (! Str::isUuid($record['uuid']) || $record['name'] !== 'QALISTING '.$record['uuid'] || $record['email'] !== 'qa-listing-'.$record['uuid'].'@example.test') {
            throw new RuntimeException('Invalid QA marker.');
        }
        $retired = DB::transaction(function () use ($record) {
            $user = User::whereKey($record['id'])->where('name', $record['name'])->where('email', $record['email'])->first();
            if (! $user) {
                return [];
            }
            $listings = Listing::where('user_id', $user->id)->pluck('id');
            $photos = ListingPhoto::whereIn('listing_id', $listings)->lockForUpdate()->get();
            $retired = [];
            foreach ($photos as $photo) {
                $retired = array_merge($retired, app(ListingPhotos::class)->retire($photo));
                $photo->delete();
            }
            DB::table('listing_events')->whereIn('listing_id', $listings)->delete();
            Listing::whereIn('id', $listings)->delete();
            $user->delete();

            return $retired;
        });
        foreach ($retired as $id) {
            app(FileCleanup::class)->remove($id);
        }
    }
    unlink($path);
    echo "Removed only the recorded listing QA sellers, listings, events and image objects.\n";
} else {
    throw new RuntimeException('Choose create, audit-photos or clean.');
}
