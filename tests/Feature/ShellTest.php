<?php

namespace Tests\Feature;

use Tests\TestCase;

class ShellTest extends TestCase
{
    public function test_home_is_server_rendered_in_romanian(): void
    {
        $this->get('/')->assertOk()->assertSee('Lucruri bune.')
            ->assertSee('lang="ro"', false)->assertSee('Acces anticipat')
            ->assertSee('ciorne de anunțuri')
            ->assertSee('noindex, nofollow');
    }

    public function test_english_has_localized_navigation_and_content(): void
    {
        $this->get('/en')->assertOk()->assertSee('Good things.')
            ->assertSee('lang="en"', false)->assertSee('Site language')
            ->assertSee('Early access')->assertSee('href="'.route('home.localized', ['locale' => 'ro']).'"', false);
    }

    public function test_romanian_explicit_route_and_invalid_language(): void
    {
        $this->get('/ro')->assertOk()->assertSee('Sari la conținut');
        $this->get('/de')->assertNotFound()->assertSee('Pagina nu a fost găsită.');
    }

    public function test_brand_name_can_change_without_template_changes(): void
    {
        config(['app.name' => 'Nume nou']);
        $this->get('/ro')->assertOk()->assertSee('Nume nou')->assertDontSee('OLX');
    }

    public function test_shell_has_security_headers_and_no_fake_marketplace_controls(): void
    {
        $this->get('/ro')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy')
            ->assertDontSee('href="#"', false)->assertDontSee('<form', false)
            ->assertDontSee('Postează un anunț');
    }

    public function test_locale_does_not_leak_between_requests(): void
    {
        $this->get('/en')->assertSee('Good things.');
        $this->get('/ro')->assertSee('Lucruri bune.')->assertDontSee('Good things.');
    }
}
