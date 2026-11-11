<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebInterfaceTest extends TestCase
{
    public function test_store_interface_is_available_with_its_static_assets(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="auth-view"', false)
            ->assertSee('id="app-shell"', false)
            ->assertSee('id="products-grid"', false)
            ->assertSee('id="orders-list"', false)
            ->assertSee('id="notifications-list"', false)
            ->assertSee('id="product-form"', false)
            ->assertSee('assets/shop.css', false)
            ->assertSee('assets/shop.js', false);

        $this->assertFileExists(public_path('assets/shop.css'));
        $this->assertFileExists(public_path('assets/shop.js'));
    }
}
