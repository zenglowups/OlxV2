<?php

namespace Tests\Feature;

use App\Models\PendingFileDeletion;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\Media\FileCleanup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToWriteFile;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $extra = []): array
    {
        return array_replace(['name' => 'Ana Pop', 'username' => 'ana_pop', 'account_type' => 'individual', 'contact_method' => 'email'], $extra);
    }

    public function test_edit_requires_an_authenticated_verified_account(): void
    {
        $this->get('/ro/account/profile')->assertRedirect('/ro/login');
        $this->actingAs(User::factory()->unverified()->create())->get('/ro/account/profile')->assertRedirect('/ro/email/verify');
    }

    public function test_owner_update_cannot_assign_another_owner_or_privileges_and_is_private_by_default(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($owner)->put('/ro/account/profile', $this->data(['user_id' => $other->id, 'status' => 'banned', 'avatar_path' => 'private/legal.pdf']))
            ->assertRedirect('/ro/account/profile');
        $profile = SellerProfile::sole();
        $this->assertSame($owner->id, $profile->user_id);
        $this->assertFalse($profile->is_public);
        $this->assertNull($profile->avatar_path);
        $this->assertSame('active', $owner->fresh()->status);
        $this->assertFalse(Gate::forUser($other)->allows('update', $profile));
        $this->get('/ro/account/profile/preview')->assertOk()->assertSee(__('profile.private_preview'));
        $this->get('/ro/sellers/ana_pop')->assertNotFound();
        $this->actingAs($other)->get('/ro/account/profile/preview')->assertRedirect('/ro/account/profile');
    }

    public function test_public_page_exposes_only_chosen_fields_and_real_evidence(): void
    {
        $owner = User::factory()->create(['created_at' => now()->subYears(4)]);
        $this->actingAs($owner)->put('/ro/account/profile', $this->data([
            'is_public' => '1', 'phone' => '0722 123 456', 'contact_method' => 'phone',
            'description' => '<script>alert("unsafe")</script>', 'location_label' => 'Cluj-Napoca',
        ]))->assertSessionHasNoErrors();
        $profile = SellerProfile::sole();
        $this->assertSame('+40722123456', $profile->phone);
        $this->assertStringNotContainsString('40722123456', DB::table('seller_profiles')->value('phone'));
        $this->assertArrayNotHasKey('phone', $profile->toArray());
        $this->app['auth']->forgetGuards();
        $this->get('/ro/sellers/ana_pop')->assertOk()->assertSee('Cluj-Napoca')
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert', false)
            ->assertSee(__('profile.email_verified'))->assertSee(__('profile.member_three'))
            ->assertDontSee($owner->email)->assertDontSee('40722123456')
            ->assertDontSee('Telefon verificat')->assertDontSee('52 de recenzii');
        $this->get('/en/sellers/ana_pop')->assertOk()->assertSee('Member for at least 3 years');
    }

    public function test_visibility_follows_verification_and_account_status(): void
    {
        $owner = User::factory()->create();
        SellerProfile::create(['user_id' => $owner->id, 'username' => 'private_seller', 'is_public' => true]);
        $this->get('/ro/sellers/private_seller')->assertOk();
        $owner->forceFill(['status' => 'banned'])->save();
        $this->get('/ro/sellers/private_seller')->assertNotFound();
        $owner->forceFill(['status' => 'suspended', 'suspended_until' => now()->addDay()])->save();
        $this->get('/ro/sellers/private_seller')->assertNotFound();
        $owner->forceFill(['suspended_until' => now()->subMinute()])->save();
        $this->get('/ro/sellers/private_seller')->assertOk();
        $owner->forceFill(['email_verified_at' => null])->save();
        $this->get('/ro/sellers/private_seller')->assertNotFound();
    }

    public function test_usernames_are_normalized_unique_and_reserved_names_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())->put('/ro/account/profile', $this->data(['username' => ' ANA_pop ']))->assertSessionHasNoErrors();
        $this->assertSame('ana_pop', SellerProfile::sole()->username);
        $this->actingAs(User::factory()->create())->put('/ro/account/profile', $this->data(['username' => 'ANA_POP']))->assertSessionHasErrors('username');
        $this->put('/ro/account/profile', $this->data(['username' => 'admin']))->assertSessionHasErrors('username');
        $this->put('/ro/account/profile', $this->data(['username' => '../escape']))->assertSessionHasErrors('username');
    }

    public function test_malformed_values_and_invalid_contact_are_validation_errors(): void
    {
        $this->actingAs(User::factory()->create())->from('/ro/account/profile')->put('/ro/account/profile', $this->data([
            'username' => ['bad'], 'description' => ['bad'], 'phone' => ['bad'], 'account_type' => 'administrator', 'is_public' => ['1'],
        ]))->assertSessionHasErrors(['username', 'description', 'phone', 'account_type', 'is_public']);
        $this->get('/ro/account/profile')->assertOk();
        $this->put('/ro/account/profile', $this->data(['contact_method' => 'phone']))->assertSessionHasErrors('phone');
        $this->put('/ro/account/profile', $this->data(['contact_method' => 'both']))->assertSessionHasErrors('contact_method');
        $this->put('/ro/account/profile', $this->data(['phone' => 'not a phone']))->assertSessionHasErrors('phone');
    }

    public function test_business_tax_disclosure_is_optional_and_never_implies_verification(): void
    {
        $owner = User::factory()->create();
        $business = $this->data(['account_type' => 'business', 'business_name' => 'Atelier Local SRL', 'tax_id' => 'RO12345678', 'is_public' => 1]);
        $this->actingAs($owner)->put('/ro/account/profile', $business)->assertSessionHasNoErrors();
        $this->assertStringNotContainsString('12345678', DB::table('seller_profiles')->value('tax_id'));
        $this->get('/ro/sellers/ana_pop')->assertOk()->assertSee('Atelier Local SRL')->assertDontSee('RO12345678')->assertDontSee('Firmă verificată');
        $this->put('/ro/account/profile', array_replace($business, ['show_tax_id' => 1]))->assertSessionHasNoErrors();
        $this->get('/ro/sellers/ana_pop')->assertOk()->assertSee('RO12345678')->assertSee(__('profile.declared_business'));
        $this->put('/ro/account/profile', $this->data())->assertSessionHasNoErrors();
        $this->assertNull(SellerProfile::sole()->tax_id);
        $this->assertNull(SellerProfile::sole()->business_name);
        $this->assertFalse(SellerProfile::sole()->show_tax_id);
        $this->get('/ro/sellers/ana_pop')->assertNotFound();
    }

    public function test_avatar_is_reencoded_and_private_until_the_owner_publishes_the_profile(): void
    {
        Storage::fake('s3');
        $owner = User::factory()->create();
        $this->actingAs($owner)->put('/ro/account/profile', $this->data(['avatar' => UploadedFile::fake()->image('untrusted-name.png', 240, 100)]))->assertSessionHasNoErrors();
        $profile = SellerProfile::sole();
        $bytes = Storage::disk('s3')->get($profile->avatar_path);
        $info = getimagesizefromstring($bytes);
        $this->assertSame([320, 320, IMAGETYPE_WEBP], array_slice($info, 0, 3));
        $this->assertStringNotContainsString('untrusted-name', $profile->avatar_path);
        $this->assertCount(1, Storage::disk('s3')->allFiles());
        $url = '/media/avatars/'.$profile->avatar_id;
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/webp')->assertHeader('Cache-Control', 'no-store, private');
        $this->app['auth']->forgetGuards();
        $this->get($url)->assertNotFound();
        $profile->update(['is_public' => true]);
        $this->get($url)->assertOk();
        $owner->forceFill(['status' => 'banned'])->save();
        $this->get($url)->assertNotFound();
    }

    public function test_avatar_replace_and_delete_retire_only_the_previous_object(): void
    {
        Storage::fake('s3');
        $this->actingAs(User::factory()->create())->put('/ro/account/profile', $this->data(['avatar' => UploadedFile::fake()->image('first.jpg', 100, 100)]))->assertSessionHasNoErrors();
        $old = SellerProfile::sole()->avatar_path;
        Storage::disk('s3')->put('unrelated/keep.txt', 'preserve');
        $this->put('/ro/account/profile', $this->data(['avatar' => UploadedFile::fake()->image('next.png', 100, 100)]))->assertSessionHasNoErrors();
        Storage::disk('s3')->assertMissing($old);
        $current = SellerProfile::sole()->avatar_path;
        $this->put('/ro/account/profile', $this->data(['remove_avatar' => 1]))->assertSessionHasNoErrors();
        Storage::disk('s3')->assertMissing($current);
        Storage::disk('s3')->assertExists('unrelated/keep.txt');
        $this->assertNull(SellerProfile::sole()->avatar_id);
        $this->assertDatabaseCount('pending_file_deletions', 0);
    }

    public function test_invalid_image_content_dimensions_and_size_are_rejected(): void
    {
        Storage::fake('s3');
        $this->actingAs(User::factory()->create());
        foreach ([
            UploadedFile::fake()->createWithContent('fake.jpg', '<?php echo "not an image";'),
            UploadedFile::fake()->createWithContent('vector.png', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            UploadedFile::fake()->image('tiny.png', 20, 20),
            UploadedFile::fake()->image('large.png', 6001, 64),
            UploadedFile::fake()->image('animation.gif', 100, 100),
            UploadedFile::fake()->image('big.jpg', 100, 100)->size(5121),
        ] as $file) {
            $this->put('/ro/account/profile', $this->data(['avatar' => $file]))->assertSessionHasErrors('avatar');
        }
        $this->assertCount(0, Storage::disk('s3')->allFiles());
        $this->assertDatabaseCount('seller_profiles', 0);
    }

    public function test_object_cleanup_failure_is_durable_and_retried_only_when_due(): void
    {
        $cleanup = app(FileCleanup::class);
        $record = $cleanup->remember('profiles/1/replaced.webp');
        Storage::shouldReceive('disk')->with('s3')->once()->andReturnSelf();
        Storage::shouldReceive('delete')->with($record->path)->once()->andReturn(false);
        $cleanup->remove($record->id);
        $this->assertSame(1, $record->fresh()->attempts);
        $this->assertTrue($record->fresh()->available_at->isFuture());
        $cleanup->remove($record->id);
        $this->travel(11)->minutes();
        Storage::shouldReceive('disk')->with('s3')->once()->andReturnSelf();
        Storage::shouldReceive('delete')->with($record->path)->once()->andReturn(true);
        $this->artisan('media:cleanup')->assertSuccessful();
        $this->assertNull(PendingFileDeletion::find($record->id));
    }

    public function test_storage_unavailability_returns_a_recoverable_form_error(): void
    {
        Storage::shouldReceive('disk')->with('s3')->once()->andReturnSelf();
        Storage::shouldReceive('put')->once()->andThrow(UnableToWriteFile::atLocation('test.webp'));
        $this->actingAs(User::factory()->create())->put('/ro/account/profile', $this->data(['avatar' => UploadedFile::fake()->image('photo.png', 100, 100)]))->assertSessionHasErrors('avatar');
        $this->assertDatabaseCount('seller_profiles', 0);
    }
}
