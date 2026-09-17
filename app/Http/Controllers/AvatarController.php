<?php

namespace App\Http\Controllers;

use App\Models\SellerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AvatarController extends Controller
{
    public function __invoke(Request $request, string $avatar)
    {
        $profile = SellerProfile::with('user:id,status,suspended_until,email_verified_at')->where('avatar_id', $avatar)->firstOrFail();
        abort_unless($profile->publiclyVisible() || ($request->user()?->id === $profile->user_id && $request->user()->canAuthenticate()), 404);

        return Storage::disk('s3')->response($profile->avatar_path, 'profile.webp', [
            'Content-Type' => 'image/webp', 'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
