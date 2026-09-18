<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryAttribute;
use App\Models\User;
use App\Services\Catalog\AttributeValidator;
use App\Services\Catalog\CategoryCatalog;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private function category(string $slug = 'test-category', ?int $parent = null): Category
    {
        return Category::create(['slug' => $slug, 'name_ro' => 'Categoria '.$slug, 'name_en' => 'Category '.$slug, 'parent_id' => $parent]);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'password' => 'catalog testing password']);
    }

    private function data(array $extra = []): array
    {
        return array_replace(['name_ro' => 'O categorie nouă', 'name_en' => 'A new category', 'slug' => 'a-new-category', 'parent_id' => null, 'icon' => 'grid', 'position' => 0, 'is_active' => 1, 'password' => 'catalog testing password'], $extra);
    }

    public function test_seed_is_idempotent_preserves_edits_and_public_pages_are_localized(): void
    {
        $this->seed(CategorySeeder::class);
        $count = Category::count();
        Category::where('slug', 'auto')->update(['name_ro' => 'Auto personalizat']);
        $this->seed(CategorySeeder::class);
        $this->assertSame($count, Category::count());
        $this->assertSame(9, Category::whereNull('parent_id')->count());
        $this->assertSame('Auto personalizat', Category::where('slug', 'auto')->value('name_ro'));
        $this->get('/ro')->assertOk()->assertSee('Auto personalizat')->assertSee('/ro/categories/electronice');
        $this->get('/en/categories')->assertOk()->assertSee('Electronics');
        $this->get('/ro/categories/electronice')->assertOk()->assertSee('Telefoane')->assertSee('Calculatoare');
        $this->get('/ro/categories/calculatoare')->assertOk()->assertSee(__('catalog.leaf_title'));
    }

    public function test_hidden_parent_hides_every_descendant_and_ancestor_traversal_has_no_fixed_depth(): void
    {
        $root = $this->category();
        $leaf = $root;
        for ($i = 0; $i < 20; $i++) {
            $leaf = $this->category('level-'.$i, $leaf->id);
        }
        $this->assertCount(21, app(CategoryCatalog::class)->ancestors($leaf));
        $this->get('/ro/categories/'.$leaf->slug)->assertOk();
        $root->update(['is_active' => false]);
        $this->get('/ro/categories/'.$leaf->slug)->assertNotFound();
        $this->get('/ro/categories')->assertOk()->assertDontSee($root->name_ro);
    }

    public function test_admin_routes_require_verified_administrator_and_password_for_writes(): void
    {
        $this->get('/ro/admin/categories')->assertRedirect('/ro/login');
        $user = User::factory()->create();
        $this->actingAs($user)->get('/ro/admin/categories')->assertForbidden();
        $this->post('/ro/admin/categories', $this->data(['is_admin' => 1]))->assertForbidden();
        $this->assertFalse($user->fresh()->is_admin);
        $this->actingAs($this->admin())->get('/ro/admin/categories')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/ro/admin/categories', $this->data(['password' => 'wrong']))->assertSessionHasErrors('password');
        $this->assertDatabaseCount('categories', 0);
        $this->actingAs(User::factory()->unverified()->create(['is_admin' => true]))->get('/ro/admin/categories')->assertRedirect('/ro/email/verify');
    }

    public function test_admin_creates_edits_and_hides_categories_with_an_audit(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/ro/admin/categories', $this->data())->assertSessionHasNoErrors();
        $category = Category::sole();
        $this->get('/ro/admin/categories/'.$category->id.'/edit')->assertOk();
        $this->put('/ro/admin/categories/'.$category->id, $this->data(['name_ro' => 'Nume schimbat', 'is_active' => 0]))->assertSessionHasNoErrors();
        $this->assertSame('Nume schimbat', $category->fresh()->name_ro);
        $this->assertFalse($category->fresh()->is_active);
        $this->assertDatabaseCount('admin_audits', 2);
        $this->assertDatabaseHas('admin_audits', ['actor_id' => $admin->id, 'action' => 'category.updated', 'subject_id' => $category->id]);
        $this->assertStringNotContainsString('catalog testing password', DB::table('admin_audits')->value('changes'));
    }

    public function test_moves_cannot_form_cycles_and_failed_write_has_no_audit(): void
    {
        $root = $this->category();
        $child = $this->category('child', $root->id);
        $this->actingAs($this->admin())->put('/ro/admin/categories/'.$root->id, $this->data(['slug' => $root->slug, 'parent_id' => $child->id]))->assertSessionHasErrors('parent_id');
        $this->assertNull($root->fresh()->parent_id);
        $this->assertDatabaseCount('admin_audits', 0);
    }

    public function test_attribute_definitions_are_typed_inherited_and_reject_unknown_options(): void
    {
        $this->seed(CategorySeeder::class);
        $category = Category::where('slug', 'calculatoare')->sole();
        $validator = app(AttributeValidator::class);
        $values = $validator->validate($category, ['condition' => 'used', 'storage' => '512', 'warranty' => '0', 'extra' => 'discard']);
        $this->assertSame(['brand' => null], ['brand' => $values['brand'] ?? null]);
        $this->assertSame(512.0, $values['storage']);
        $this->assertFalse($values['warranty']);
        $this->assertArrayNotHasKey('extra', $values);
        $this->assertSame([], $validator->validate($category, [], false));
        $this->expectException(ValidationException::class);
        $validator->validate($category, ['condition' => 'invented', 'storage' => -1]);
    }

    public function test_required_attributes_are_enforced_at_completion(): void
    {
        $this->seed(CategorySeeder::class);
        $this->expectException(ValidationException::class);
        app(AttributeValidator::class)->validate(Category::where('slug', 'autoturisme')->sole(), []);
    }

    public function test_admin_attribute_validation_scoping_and_immutable_keys(): void
    {
        $category = $this->category();
        $other = $this->category('other');
        $data = ['key' => 'condition', 'label_ro' => 'Stare', 'label_en' => 'Condition', 'type' => 'select', 'options_lines' => "new|Nou|New\nused|Folosit|Used", 'position' => 0, 'is_active' => 1, 'is_required' => 1, 'password' => 'catalog testing password'];
        $this->actingAs($this->admin())->post('/ro/admin/categories/'.$category->id.'/attributes', $data)->assertSessionHasNoErrors();
        $attribute = CategoryAttribute::sole();
        $this->assertSame('new', $attribute->options[0]['value']);
        $this->get('/ro/admin/categories/'.$category->id.'/attributes/'.$attribute->id.'/edit')->assertOk();
        $this->put('/ro/admin/categories/'.$other->id.'/attributes/'.$attribute->id, $data)->assertNotFound();
        $this->put('/ro/admin/categories/'.$category->id.'/attributes/'.$attribute->id, array_replace($data, ['key' => 'changed']))->assertSessionHasErrors('key');
        $this->post('/ro/admin/categories/'.$other->id.'/attributes', array_replace($data, ['options_lines' => 'broken']))->assertSessionHasErrors('options_lines');
        $this->assertSame('condition', $attribute->fresh()->key);
    }

    public function test_conflicting_inherited_keys_rollback_category_move(): void
    {
        $root = $this->category();
        $other = $this->category('other');
        foreach ([$root, $other] as $category) {
            CategoryAttribute::create(['category_id' => $category->id, 'key' => 'brand', 'label_ro' => 'Marcă', 'label_en' => 'Brand', 'type' => 'text']);
        }
        $this->actingAs($this->admin())->put('/ro/admin/categories/'.$other->id, $this->data(['slug' => $other->slug, 'parent_id' => $root->id]))->assertSessionHasErrors('key');
        $this->assertNull($other->fresh()->parent_id);
    }

    public function test_console_access_requires_real_verified_individual_and_revokes_sessions(): void
    {
        $user = User::factory()->unverified()->create();
        $this->artisan('admin:access', ['user' => $user->id])->assertFailed();
        $user->forceFill(['email_verified_at' => now()])->save();
        $this->artisan('admin:access', ['user' => $user->id])->assertSuccessful();
        $this->assertTrue($user->fresh()->is_admin);
        $this->assertSame(2, $user->fresh()->auth_version);
        $this->actingAs($user->fresh())->put('/ro/account/profile', ['name' => 'Admin User', 'username' => 'admin_user', 'account_type' => 'business', 'business_name' => 'Company', 'contact_method' => 'email'])->assertSessionHasErrors('account_type');
        $this->artisan('admin:access', ['user' => $user->id, '--revoke' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->is_admin);
        $this->assertSame(3, $user->fresh()->auth_version);
    }

    public function test_public_catalog_queries_paginate_in_sql(): void
    {
        for ($i = 0; $i < 27; $i++) {
            $this->category('page-'.$i);
        }
        $response = $this->get('/en/categories')->assertOk();
        $this->assertCount(24, $response->viewData('categories')->items());
        $this->get('/en/categories?page=2')->assertOk()->assertSee('page-26')->assertDontSee('page-0');
    }
}
