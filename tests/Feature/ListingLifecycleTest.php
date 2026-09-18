<?php

namespace Tests\Feature;

use App\Jobs\ProcessListingPhoto;
use App\Models\Category;
use App\Models\County;
use App\Models\Listing;
use App\Models\ListingPhoto;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\Listings\DraftWriter;
use App\Services\Media\FileCleanup;
use App\Services\Media\ListingImageProcessor;
use App\Services\Media\ListingPhotos;
use Database\Seeders\CategorySeeder;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CategorySeeder::class, LocationSeeder::class]);
        Storage::fake('s3');
        Queue::fake();
    }

    private function complete(?User $user = null): Listing
    {
        $user ??= User::factory()->create();
        SellerProfile::firstOrCreate(['user_id' => $user->id], ['username' => 'seller-'.Str::lower(Str::random(12)), 'is_public' => true, 'phone' => '0722123456', 'tax_id' => 'PRIVATE-TAX']);
        $listing = app(DraftWriter::class)->create($user, Category::where('slug', 'calculatoare')->value('id'), (string) Str::uuid());
        $listing->fill(['title' => 'Laptop personal pentru lucru', 'description' => 'Calculator îngrijit, folosit pentru lucru și lectură. Include încărcătorul original.', 'attributes' => ['condition' => 'used', 'warranty' => false, 'storage' => 512, 'unknown' => 'PRIVATE_OBSOLETE_ATTRIBUTE'], 'pricing_mode' => 'negotiable', 'price_minor' => 125005, 'currency' => 'RON', 'county_id' => County::where('code', 'CJ')->value('id'), 'locality' => 'Cluj-Napoca', 'contact_method' => 'email'])->save();

        return $listing;
    }

    private function publish(Listing $listing)
    {
        return $this->post('/ro/account/drafts/'.$listing->public_id.'/publish', ['version' => $listing->fresh()->version]);
    }

    private function action(Listing $listing, string $action, ?int $version = null)
    {
        return $this->post('/ro/account/listings/'.$listing->public_id.'/'.$action, ['version' => $version ?? $listing->fresh()->version]);
    }

    private function publicUrl(Listing $listing): string
    {
        return '/ro/listings/'.$listing->public_id;
    }

    private function photo(Listing $listing): ListingPhoto
    {
        app(ListingPhotos::class)->upload($listing->user, $listing, [UploadedFile::fake()->image('phone-private.jpg', 800, 600)], $listing->fresh()->version);
        $photo = ListingPhoto::where('listing_id', $listing->id)->firstOrFail();
        (new ProcessListingPhoto($photo->id))->handle(app(ListingImageProcessor::class), app(FileCleanup::class));

        return $photo->fresh();
    }

    public function test_publication_is_validated_audited_immediate_and_lasts_thirty_days(): void
    {
        $this->travelTo(now()->startOfSecond());
        $listing = $this->complete();
        $this->actingAs($listing->user);
        $this->publish($listing)->assertRedirect($this->publicUrl($listing))->assertSessionHasNoErrors();
        $listing->refresh();
        $this->assertSame('active', $listing->status);
        $this->assertSame('unreviewed', $listing->moderation_state);
        $this->assertTrue($listing->expires_at->equalTo(now()->addDays(30)));
        $this->assertTrue($listing->published_at->equalTo(now()));
        $this->assertSame(2, $listing->version);
        $this->assertArrayNotHasKey('unknown', $listing->attributes);
        $this->assertDatabaseHas('listing_events', ['listing_id' => $listing->id, 'actor_id' => $listing->user_id, 'action' => 'publish', 'from_status' => 'draft', 'to_status' => 'active', 'version' => 2]);
        auth()->logout();
        $this->get($this->publicUrl($listing))->assertOk()->assertSee($listing->title)->assertSee('1.250,05 RON')->assertDontSee($listing->user->email)->assertDontSee('0722123456')->assertDontSee('PRIVATE-TAX')->assertDontSee('PRIVATE_OBSOLETE_ATTRIBUTE')->assertHeader('Cache-Control', 'no-store, private');
        $this->get(str_replace('/ro/', '/en/', $this->publicUrl($listing)))->assertOk()->assertSee('About the seller');
        $this->get('/ro/listings')->assertOk()->assertSee($listing->title);
        $this->get('/ro/categories/electronice')->assertOk()->assertSee($listing->title);
        $this->get('/ro/sellers/'.$listing->user->profile->username)->assertOk()->assertSee($listing->title);
        $this->get('/ro')->assertOk()->assertSee($listing->title);
    }

    public function test_incomplete_stale_or_invalid_publication_cannot_publish(): void
    {
        $listing = $this->complete();
        $this->actingAs($listing->user);
        $listing->update(['title' => 'Short', 'attributes' => [], 'county_id' => null, 'price_minor' => null]);
        $this->publish($listing)->assertSessionHasErrors(['title', 'attributes.condition', 'county_id', 'price']);
        $this->assertSame('draft', $listing->fresh()->status);
        $this->assertDatabaseCount('listing_events', 0);
        $this->assertNull($listing->fresh()->published_at);
        $this->post('/ro/account/drafts/'.$listing->public_id.'/publish', ['version' => 999])->assertStatus(409);
        $this->assertDatabaseCount('listing_events', 0);
    }

    public function test_public_profile_and_ready_photos_are_required_without_changing_privacy_settings(): void
    {
        $listing = $this->complete();
        $this->actingAs($listing->user);
        $listing->user->profile->update(['is_public' => false]);
        $this->publish($listing)->assertSessionHasErrors('profile');
        $this->assertFalse($listing->user->profile->fresh()->is_public);
        $listing->user->profile->update(['is_public' => true]);
        app(ListingPhotos::class)->upload($listing->user, $listing, [UploadedFile::fake()->image('pending.jpg', 100, 100)], 1);
        $this->publish($listing)->assertSessionHasErrors('photos');
        ListingPhoto::query()->update(['status' => 'failed']);
        $this->publish($listing)->assertSessionHasErrors('photos');
        $this->assertSame('draft', $listing->fresh()->status);
    }

    public function test_actual_public_images_are_revoked_by_edit_sold_remove_expiry_profile_and_moderation(): void
    {
        $listing = $this->complete();
        $photo = $this->photo($listing);
        $this->actingAs($listing->user);
        $this->publish($listing)->assertSessionHasNoErrors();
        $listing->refresh();
        auth()->logout();
        $url = '/media/listings/'.$photo->public_id.'/card';
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/webp')->assertHeader('Cache-Control', 'no-store, private');
        foreach (['draft', 'sold', 'expired', 'removed', 'suspended'] as $status) {
            $listing->forceFill(['status' => $status])->save();
            $this->get($url)->assertNotFound();
            $this->get($this->publicUrl($listing))->assertNotFound();
        }
        $listing->forceFill(['status' => 'active'])->save();
        foreach (['hidden', 'rejected', 'changes_requested'] as $moderation) {
            $listing->forceFill(['moderation_state' => $moderation])->save();
            $this->get($url)->assertNotFound();
        }
        $listing->forceFill(['moderation_state' => 'reviewed'])->save();
        $listing->user->profile->update(['is_public' => false]);
        $this->get($url)->assertNotFound();
        $listing->user->profile->update(['is_public' => true]);
        $listing->category->update(['is_active' => false]);
        $this->get($url)->assertNotFound();
        $listing->category->update(['is_active' => true]);
        $listing->forceFill(['expires_at' => now()])->save();
        $this->get($url)->assertNotFound();
    }

    public function test_ownership_authentication_and_verification_protect_all_lifecycle_operations(): void
    {
        $listing = $this->complete();
        $this->publish($listing)->assertRedirect('/ro/login');
        $this->actingAs(User::factory()->create());
        $this->publish($listing)->assertNotFound();
        foreach (['edit', 'reserve', 'activate', 'sold', 'renew', 'remove'] as $action) {
            $this->get('/ro/account/listings/'.$listing->public_id.'/'.$action)->assertNotFound();
            $this->action($listing, $action)->assertNotFound();
        }
        $this->actingAs(User::factory()->unverified()->create())->get('/ro/account/listings')->assertRedirect('/ro/email/verify');
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/ro/account/listings')->assertForbidden();
        $this->assertDatabaseCount('listing_events', 0);
    }

    public function test_reservation_sale_and_renewal_have_explicit_valid_transitions(): void
    {
        $this->travelTo(now()->startOfSecond());
        $listing = $this->complete();
        $this->actingAs($listing->user);
        $this->action($listing, 'sold')->assertSessionHasErrors('action');
        $this->publish($listing);
        $expires = $listing->fresh()->expires_at;
        $this->action($listing, 'reserve')->assertSessionHasNoErrors();
        $this->assertSame('reserved', $listing->fresh()->status);
        $this->assertTrue($expires->equalTo($listing->fresh()->expires_at));
        $this->get($this->publicUrl($listing))->assertSee(__('market.reserved_note'));
        $this->action($listing, 'activate')->assertSessionHasNoErrors();
        $this->assertSame('active', $listing->fresh()->status);
        $this->action($listing, 'renew')->assertSessionHasErrors('action');
        $this->action($listing, 'sold')->assertSessionHasNoErrors();
        $this->get($this->publicUrl($listing))->assertNotFound();
        $this->travel(2)->days();
        $this->action($listing, 'renew')->assertSessionHasNoErrors();
        $this->assertSame('active', $listing->fresh()->status);
        $this->assertTrue($listing->fresh()->expires_at->equalTo(now()->addDays(30)));
        $this->assertNotNull($listing->fresh()->renewed_at);
        $this->assertDatabaseCount('listing_events', 5);
    }

    public function test_edit_withdraws_before_partial_changes_and_republication_preserves_address_and_deadline(): void
    {
        $listing = $this->complete();
        $this->actingAs($listing->user);
        $this->publish($listing);
        $expires = $listing->fresh()->expires_at;
        $published = $listing->fresh()->published_at;
        $this->get('/ro/account/listings/'.$listing->public_id.'/edit')->assertOk()->assertSee(__('market.confirm_edit'));
        $this->action($listing, 'edit')->assertRedirect('/ro/account/drafts/'.$listing->public_id.'/edit/details');
        $this->get($this->publicUrl($listing))->assertNotFound();
        $this->patchJson('/ro/account/drafts/'.$listing->public_id.'/steps/details', ['version' => $listing->fresh()->version, 'title' => 'Scurt'])->assertOk();
        $this->publish($listing)->assertSessionHasErrors('title');
        $this->patchJson('/ro/account/drafts/'.$listing->public_id.'/steps/details', ['version' => $listing->fresh()->version, 'title' => 'Laptop actualizat pentru vânzare'])->assertOk();
        $this->publish($listing)->assertRedirect($this->publicUrl($listing))->assertSessionHasNoErrors();
        $this->assertTrue($expires->equalTo($listing->fresh()->expires_at));
        $this->assertTrue($published->equalTo($listing->fresh()->published_at));
        $this->assertSame('unreviewed', $listing->fresh()->moderation_state);
    }

    public function test_expiration_is_immediate_without_scheduler_and_scheduler_is_idempotent(): void
    {
        $listing = $this->complete();
        $this->actingAs($listing->user);
        $this->publish($listing);
        $listing->forceFill(['expires_at' => now()->subSecond()])->save();
        $this->get($this->publicUrl($listing))->assertNotFound();
        $this->get('/ro/listings')->assertDontSee($listing->title);
        $this->get('/ro/account/listings?status=expired')->assertOk()->assertSee($listing->title);
        $this->artisan('listings:expire')->assertSuccessful();
        $this->artisan('listings:expire')->assertSuccessful();
        $this->assertSame('expired', $listing->fresh()->status);
        $this->assertSame(1, DB::table('listing_events')->where('action', 'expire')->count());
        $this->action($listing, 'renew', 2)->assertStatus(409);
        $this->action($listing, 'renew')->assertSessionHasNoErrors();
        $this->assertSame('active', $listing->fresh()->status);
    }

    public function test_removal_retains_audit_and_images_privately_and_blocks_reactivation(): void
    {
        $listing = $this->complete();
        $photo = $this->photo($listing);
        $this->actingAs($listing->user);
        $this->publish($listing);
        $this->action($listing, 'remove')->assertSessionHasNoErrors();
        $this->assertSame('removed', $listing->fresh()->status);
        $this->assertNotNull($listing->fresh()->removed_at);
        Storage::disk('s3')->assertExists($photo->variantPath('full'));
        foreach (['edit', 'renew', 'reserve', 'activate', 'sold', 'remove'] as $action) {
            $this->action($listing, $action)->assertSessionHasErrors('action');
        }
        $this->publish($listing)->assertNotFound();
        $this->get('/ro/account/listings?status=removed')->assertOk()->assertSee($listing->title);
        $this->assertDatabaseCount('listing_events', 2);
    }

    public function test_hidden_moderation_suspension_and_changed_taxonomy_cannot_be_bypassed(): void
    {
        $listing = $this->complete();
        $this->actingAs($listing->user);
        $listing->forceFill(['moderation_state' => 'hidden'])->save();
        $this->publish($listing)->assertSessionHasErrors('moderation');
        $this->action($listing, 'remove')->assertSessionHasErrors('action');
        $listing->forceFill(['moderation_state' => 'unreviewed'])->save();
        Category::whereKey($listing->category->parent_id)->update(['is_active' => false]);
        $this->publish($listing)->assertSessionHasErrors('category_id');
        Category::whereKey($listing->category->parent_id)->update(['is_active' => true]);
        $this->publish($listing)->assertSessionHasNoErrors();
        $listing->forceFill(['status' => 'suspended'])->save();
        foreach (['edit', 'renew', 'activate'] as $action) {
            $this->action($listing, $action)->assertSessionHasErrors('action');
        }
        $this->assertSame('suspended', $listing->fresh()->status);
    }

    public function test_seller_bans_unverification_and_parent_category_visibility_hide_feed_and_images(): void
    {
        $listing = $this->complete();
        $photo = $this->photo($listing);
        $this->actingAs($listing->user);
        $this->publish($listing);
        auth()->logout();
        $owner = $listing->user;
        foreach ([['status' => 'banned'], ['status' => 'suspended', 'suspended_until' => now()->addDay()], ['status' => 'active', 'email_verified_at' => null], ['status' => 'active', 'email_verified_at' => now(), 'is_admin' => true]] as $change) {
            $owner->forceFill($change)->save();
            $this->get($this->publicUrl($listing))->assertNotFound();
            $this->get('/media/listings/'.$photo->public_id.'/card')->assertNotFound();
            $this->get('/ro/listings')->assertDontSee($listing->title);
        }
        $owner->forceFill(['status' => 'suspended', 'suspended_until' => now()->subDay(), 'email_verified_at' => now(), 'is_admin' => false])->save();
        $this->get($this->publicUrl($listing))->assertOk();
        Category::whereKey($listing->category->parent_id)->update(['is_active' => false]);
        $this->get($this->publicUrl($listing))->assertNotFound();
        $this->get('/ro/listings')->assertDontSee($listing->title);
    }

    public function test_management_uses_real_owner_counts_filters_and_paginated_rows(): void
    {
        $owner = User::factory()->create();
        for ($index = 0; $index < 14; $index++) {
            $listing = $this->complete($owner);
            if ($index < 3) {
                $listing->forceFill(['status' => 'active', 'published_at' => now(), 'expires_at' => now()->addDay()])->save();
            }
        }
        $other = $this->complete();
        $other->update(['title' => 'Other owner private title']);
        $this->actingAs($owner)->get('/ro/account/listings')->assertOk()->assertDontSee($other->title)->assertViewHas('listings', fn ($rows) => $rows->count() === 12 && $rows->hasMorePages())->assertViewHas('counts', fn ($counts) => (int) $counts['active'] === 3 && (int) $counts['draft'] === 11);
        $this->get('/ro/account/listings?status=active')->assertOk()->assertViewHas('listings', fn ($rows) => $rows->count() === 3);
        $this->get('/ro/account/listings?status[]=active')->assertSessionHasErrors('status');
        $this->get('/ro/account/listings?status=invented')->assertSessionHasErrors('status');
    }
}
