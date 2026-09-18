<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\County;
use App\Models\Listing;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\Listings\DraftValidator;
use App\Services\Listings\DraftWriter;
use Database\Seeders\CategorySeeder;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DraftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CategorySeeder::class, LocationSeeder::class]);
    }

    private function draft(?User $user = null, string $category = 'calculatoare'): Listing
    {
        $user ??= User::factory()->create();

        return app(DraftWriter::class)->create($user, Category::where('slug', $category)->value('id'), (string) Str::uuid());
    }

    private function url(Listing $listing, string $step): string
    {
        return '/ro/account/drafts/'.$listing->public_id.'/steps/'.$step;
    }

    private function autosave(Listing $listing, string $step, array $data)
    {
        return $this->patchJson($this->url($listing, $step), ['version' => $listing->fresh()->version, ...$data]);
    }

    public function test_category_selection_creates_one_private_draft_and_idempotently_resumes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/ro/sell')->assertOk()->assertSee('Electronice');
        $this->get('/en/sell?category=calculatoare')->assertOk()->assertSee('Computers');
        $payload = ['category_id' => Category::where('slug', 'calculatoare')->value('id'), 'creation_token' => (string) Str::uuid(), 'user_id' => 500, 'status' => 'active', 'moderation_state' => 'approved'];
        $this->post('/ro/account/drafts', $payload)->assertRedirect();
        $listing = Listing::sole();
        $this->post('/ro/account/drafts', $payload)->assertRedirect('/ro/account/drafts/'.$listing->public_id.'/edit/details');
        $this->assertDatabaseCount('listings', 1);
        $this->assertSame($user->id, $listing->user_id);
        $this->assertSame('draft', $listing->status);
        $this->assertSame('unreviewed', $listing->moderation_state);
        $this->assertNull($listing->published_at);
        $this->get('/ro/account/drafts')->assertOk()->assertSee($listing->public_id)->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_every_draft_endpoint_rejects_other_owners_guests_unverified_and_admin_accounts(): void
    {
        $listing = $this->draft();
        $paths = ['/ro/account/drafts/'.$listing->public_id.'/edit/details', '/ro/account/drafts/'.$listing->public_id.'/preview', '/ro/sell?draft='.$listing->public_id];
        foreach ($paths as $path) {
            $this->get($path)->assertRedirect('/ro/login');
        }
        $this->actingAs(User::factory()->create());
        foreach ($paths as $path) {
            $this->get($path)->assertNotFound();
        }
        foreach (array_diff(DraftValidator::STEPS, ['preview']) as $step) {
            $this->patchJson($this->url($listing, $step), ['version' => 1])->assertNotFound();
            $this->post($this->url($listing, $step), ['version' => 1])->assertNotFound();
        }
        $this->actingAs(User::factory()->unverified()->create())->get('/ro/sell')->assertRedirect('/ro/email/verify');
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/ro/sell')->assertForbidden();
        $this->actingAs($listing->user)->get('/ro/account/drafts/'.$listing->public_id.'/edit/details')->assertOk();
        $listing->user->update(['name' => 'Owner']);
        DB::table('users')->where('id', $listing->user_id)->update(['status' => 'banned']);
        $this->actingAs($listing->user->fresh())->get('/ro/account/drafts/'.$listing->public_id.'/preview')->assertRedirect('/ro/login');
    }

    public function test_partial_progress_persists_missing_fields_are_preserved_and_full_step_validates(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $this->autosave($listing, 'details', ['title' => 'La', 'description' => 'Descriere începută', 'user_id' => 999, 'status' => 'active'])->assertOk()->assertJsonPath('version', 2);
        $this->autosave($listing, 'details', ['title' => 'Laptop personal bine întreținut'])->assertOk();
        $this->assertSame('Descriere începută', $listing->fresh()->description);
        $this->assertSame('draft', $listing->fresh()->status);
        $this->post($this->url($listing, 'details'), ['version' => 3, 'title' => 'Scurt', 'description' => 'Scurt'])->assertSessionHasErrors(['title', 'description']);
        $this->assertSame(3, $listing->fresh()->version);
        $this->post($this->url($listing, 'details'), ['version' => 3, 'title' => 'Laptop personal bine întreținut', 'description' => 'Calculator personal îngrijit, folosit pentru lucru și lectură.'])->assertRedirect('/ro/account/drafts/'.$listing->public_id.'/edit/attributes');
        $this->get('/ro/account/drafts/'.$listing->public_id.'/edit/details')->assertSee('Laptop personal bine întreținut')->assertSee('Calculator personal îngrijit');
        $this->assertSame('attributes', $listing->fresh()->last_step);
    }

    public function test_stale_tab_cannot_overwrite_and_gets_recoverable_conflict(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $this->autosave($listing, 'details', ['title' => 'Versiunea nouă'])->assertOk();
        $this->patchJson($this->url($listing, 'details'), ['version' => 1, 'title' => 'Versiune veche'])->assertStatus(409)->assertJsonPath('conflict', true);
        $this->post($this->url($listing, 'details'), ['version' => 1, 'title' => '<script>old</script>', 'description' => 'Text recuperabil'])->assertStatus(409)->assertSee('&lt;script&gt;old&lt;/script&gt;', false)->assertSee('Text recuperabil')->assertSee('versiune mai nouă');
        $this->assertSame('Versiunea nouă', $listing->fresh()->title);
        $this->assertSame(2, $listing->fresh()->version);
    }

    public function test_dynamic_attributes_keep_false_and_zero_and_check_live_rules(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $this->autosave($listing, 'attributes', ['attributes' => ['condition' => 'used', 'warranty' => '0', 'storage' => '512', 'brand' => 'Laptop', 'unknown' => 'discard']])->assertOk();
        $this->autosave($listing, 'attributes', ['attributes' => ['storage' => '1024']])->assertOk();
        $data = $listing->fresh()->attributes;
        $this->assertFalse($data['warranty']);
        $this->assertSame('used', $data['condition']);
        $this->assertEquals(1024, $data['storage']);
        $this->assertArrayNotHasKey('unknown', $data);
        $this->autosave($listing, 'attributes', ['attributes' => ['condition' => 'invented', 'storage' => -1]])->assertUnprocessable()->assertJsonValidationErrors(['attributes.condition', 'attributes.storage']);
        $this->get('/ro/account/drafts/'.$listing->public_id.'/preview')->assertOk()->assertSee('Nu');
        $car = $this->draft($listing->user, 'autoturisme');
        $this->post($this->url($car, 'attributes'), ['version' => 1, 'attributes' => []])->assertSessionHasErrors('attributes.brand');
        $this->autosave($car, 'attributes', ['attributes' => ['mileage' => '0']])->assertOk();
        $this->assertEquals(0, $car->fresh()->attributes['mileage']);
    }

    public function test_price_modes_store_integer_money_and_clear_irrelevant_amounts(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $this->autosave($listing, 'price', ['pricing_mode' => 'negotiable', 'currency' => 'EUR', 'price' => '1250,05'])->assertOk();
        $this->assertSame(125005, $listing->fresh()->price_minor);
        $this->assertSame('1250.05', $listing->fresh()->priceInput());
        foreach (['-1', '1e3', '1.234', '1,000.00', '1000000000', ['malformed']] as $bad) {
            $this->autosave($listing, 'price', ['price' => $bad])->assertUnprocessable();
        }
        $this->autosave($listing, 'price', ['price' => '0'])->assertUnprocessable()->assertJsonValidationErrors('price');
        $this->autosave($listing, 'price', ['pricing_mode' => 'free', 'price' => 'bad'])->assertOk();
        $this->assertSame(0, $listing->fresh()->price_minor);
        foreach (['exchange', 'contact'] as $mode) {
            $this->autosave($listing, 'price', ['pricing_mode' => $mode])->assertOk();
            $this->assertNull($listing->fresh()->price_minor);
        }
        $this->post($this->url($listing, 'price'), ['version' => $listing->fresh()->version, 'pricing_mode' => 'fixed', 'currency' => 'RON'])->assertSessionHasErrors('price');
    }

    public function test_county_country_constraints_and_phone_requirements_protect_contact(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $county = County::where('code', 'CJ')->sole();
        $this->autosave($listing, 'location', ['country_code' => 'RO', 'county_id' => $county->id, 'locality' => 'Cluj-Napoca'])->assertOk();
        $this->autosave($listing, 'location', ['country_code' => 'XX', 'county_id' => 99999])->assertUnprocessable();
        $this->autosave($listing, 'location', ['locality' => '<script>oops</script>'])->assertUnprocessable();
        $this->autosave($listing, 'contact', ['contact_method' => 'phone'])->assertUnprocessable()->assertJsonValidationErrors('contact_method');
        $profile = new SellerProfile(['username' => 'contact-test', 'phone' => '+40712345678']);
        $profile->user_id = $listing->user_id;
        $profile->save();
        $this->autosave($listing, 'contact', ['contact_method' => 'phone', 'phone' => '+40999999999'])->assertOk();
        $this->get('/ro/account/drafts/'.$listing->public_id.'/preview')->assertOk()->assertSee('Cluj-Napoca')->assertDontSee('+40712345678')->assertDontSee($listing->user->email);
        $profile->phone = null;
        $profile->save();
        $this->get('/ro/account/drafts/'.$listing->public_id.'/preview')->assertSee(__('listing.preview_missing'));
    }

    public function test_category_change_clears_old_attributes_and_hidden_categories_cannot_be_selected(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $this->autosave($listing, 'attributes', ['attributes' => ['condition' => 'used']])->assertOk();
        $new = Category::where('slug', 'biciclete')->sole();
        $this->post($this->url($listing, 'category'), ['version' => 2, 'category_id' => $new->id])->assertRedirect();
        $this->assertSame([], $listing->fresh()->attributes);
        $this->assertSame($new->id, $listing->fresh()->category_id);
        $this->autosave($listing, 'category', ['category_id' => Category::where('slug', 'sport')->value('id')])->assertUnprocessable();
        Category::where('slug', 'sport')->update(['is_active' => false]);
        $this->autosave($listing, 'category', ['category_id' => $new->id])->assertUnprocessable();
        $this->get('/ro/sell?category=biciclete')->assertNotFound();
        $this->get('/ro/account/drafts/'.$listing->public_id.'/preview')->assertOk()->assertSee(__('listing.preview_missing'));
    }

    public function test_limits_and_listing_state_are_enforced_before_mutations(): void
    {
        config(['listings.draft_limit' => 1]);
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $this->post('/ro/account/drafts', ['category_id' => $listing->category_id, 'creation_token' => (string) Str::uuid()])->assertSessionHasErrors('category_id');
        $listing->forceFill(['status' => 'active'])->save();
        $this->autosave($listing, 'details', ['title' => 'Cannot change active listing here'])->assertNotFound();
        $this->get('/ro/account/drafts/'.$listing->public_id.'/preview')->assertNotFound();
        $this->assertSame('', $listing->fresh()->title);
    }

    public function test_preview_revalidates_all_sections_and_escapes_seller_content(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $payloads = [
            'details' => ['title' => 'Laptop personal pentru lucru', 'description' => '<script>alert(1)</script> Un calculator personal, cu încărcător inclus.'],
            'attributes' => ['attributes' => ['condition' => 'used']],
            'price' => ['pricing_mode' => 'fixed', 'currency' => 'RON', 'price' => '2400'],
            'location' => ['country_code' => 'RO', 'county_id' => County::where('code', 'CJ')->value('id'), 'locality' => 'Cluj-Napoca'],
            'contact' => ['contact_method' => 'email'],
        ];
        foreach ($payloads as $step => $data) {
            $this->post($this->url($listing, $step), ['version' => $listing->fresh()->version, ...$data])->assertSessionHasNoErrors()->assertRedirect();
        }
        $this->get('/ro/account/drafts/'.$listing->public_id.'/preview')->assertOk()->assertSee(__('listing.preview_ready'))->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)', false)->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame('draft', $listing->fresh()->status);
        $this->assertNull($listing->fresh()->expires_at);
        Category::where('id', $listing->category_id)->update(['is_active' => false]);
        $this->get('/ro/account/drafts/'.$listing->public_id.'/preview')->assertSee(__('listing.preview_missing'));
    }

    public function test_malformed_payloads_never_raise_server_errors_or_change_version(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        foreach ([
            ['details', ['title' => ['bad'], 'description' => ['bad']]],
            ['attributes', ['attributes' => 'bad']],
            ['attributes', ['attributes' => ['condition' => ['bad']]]],
            ['price', ['pricing_mode' => ['bad'], 'currency' => ['bad'], 'price' => ['bad']]],
            ['location', ['country_code' => ['bad'], 'county_id' => ['bad'], 'locality' => ['bad']]],
            ['contact', ['contact_method' => ['bad']]],
            ['category', ['category_id' => ['bad']]],
        ] as [$step, $data]) {
            $this->autosave($listing, $step, $data)->assertUnprocessable();
        }
        $this->assertSame(1, $listing->fresh()->version);
        $this->get('/ro/account/drafts/'.$listing->public_id.'/edit/unknown')->assertNotFound();
        $this->get('/ro/sell?category[]=bad')->assertSessionHasErrors('category');
    }

    public function test_save_and_exit_accepts_incomplete_data_and_seed_does_not_override_counties(): void
    {
        $listing = $this->draft();
        $this->actingAs($listing->user);
        $this->post($this->url($listing, 'details'), ['version' => 1, 'intent' => 'exit', 'title' => 'Un', 'description' => ''])->assertRedirect('/ro/account/drafts');
        $this->assertSame('Un', $listing->fresh()->title);
        $this->assertSame('details', $listing->fresh()->last_step);
        $this->assertDatabaseCount('counties', 42);
        DB::table('counties')->where('code', 'CJ')->update(['name' => 'Custom Cluj']);
        $this->seed(LocationSeeder::class);
        $this->assertDatabaseCount('counties', 42);
        $this->assertDatabaseHas('counties', ['code' => 'CJ', 'name' => 'Custom Cluj']);
    }

    public function test_drafts_are_paginated_and_do_not_include_other_accounts(): void
    {
        $owner = User::factory()->create();
        for ($i = 0; $i < 14; $i++) {
            $this->draft($owner);
        }
        $other = $this->draft();
        $this->actingAs($owner)->get('/ro/account/drafts')->assertOk()->assertDontSee($other->public_id)->assertViewHas('drafts', fn ($drafts) => $drafts->count() === 12 && $drafts->total() === 14);
        $this->get('/ro/account/drafts?page=2')->assertOk()->assertViewHas('drafts', fn ($drafts) => $drafts->count() === 2);
    }
}
