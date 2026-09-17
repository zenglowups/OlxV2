<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\Media\AvatarProcessor;
use App\Services\Media\FileCleanup;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profiles.edit', ['profile' => SellerProfile::firstOrNew(['user_id' => $request->user()->id])]);
    }

    public function update(ProfileRequest $request, AvatarProcessor $images, FileCleanup $cleanup)
    {
        $data = $request->validated();
        try {
            $new = $request->hasFile('avatar') ? $images->store($request->file('avatar'), $request->user()->id, $data['account_type'] === 'business') : null;
        } catch (FilesystemException $exception) {
            report($exception);
            throw ValidationException::withMessages(['avatar' => __('profile.avatar_error')]);
        }
        $retired = null;
        try {
            DB::transaction(function () use ($request, $data, $new, $cleanup, &$retired) {
                $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $profile = SellerProfile::firstOrNew(['user_id' => $user->id]);
                Gate::forUser($user)->authorize('update', $profile);
                $user->fill(['name' => $data['name'], 'account_type' => $data['account_type']])->save();
                $profile->fill(Arr::except($data, ['name', 'account_type', 'avatar', 'remove_avatar']));
                $profile->is_public = $request->boolean('is_public');
                $profile->show_tax_id = $data['account_type'] === 'business' && $request->boolean('show_tax_id');
                if ($data['account_type'] !== 'business') {
                    $profile->business_name = null;
                    $profile->tax_id = null;
                }
                if ($new || $request->boolean('remove_avatar')) {
                    if ($profile->avatar_path) {
                        $retired = $cleanup->remember($profile->avatar_path)->id;
                    }
                    $profile->avatar_id = $new['id'] ?? null;
                    $profile->avatar_path = $new['path'] ?? null;
                }
                $profile->save();
            });
        } catch (\Throwable $exception) {
            if ($new) {
                $cleanup->remove($cleanup->remember($new['path'])->id);
            }
            if ($exception instanceof UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['username' => __('profile.username_taken')]);
            }
            throw $exception;
        }
        if ($retired) {
            $cleanup->remove($retired);
        }

        return redirect()->route('profile.edit')->with('status', __('profile.saved'));
    }

    public function show(SellerProfile $profile)
    {
        $profile->load('user:id,name,account_type,email_verified_at,created_at,status,suspended_until');
        abort_unless($profile->publiclyVisible(), 404);

        return view('profiles.show', ['profile' => $profile, 'preview' => false]);
    }

    public function preview(Request $request)
    {
        $profile = SellerProfile::with('user')->where('user_id', $request->user()->id)->first();
        if (! $profile) {
            return redirect()->route('profile.edit');
        }
        Gate::authorize('update', $profile);

        return view('profiles.show', ['profile' => $profile, 'preview' => true]);
    }
}
