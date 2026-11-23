<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_factotum_navigation(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Factotum');
        $response->assertSee('Contatos');
        $response->assertSee('Obrigações');
        $response->assertSee('Mensagens');
        $response->assertSee('Modelos');
        $response->assertSee('Configurações');
        $response->assertDontSee("Let's get started");
        $response->assertDontSee('Campanhas');
    }

    public function test_future_modules_show_a_pending_page(): void
    {
        $modules = [
            'obligations' => 'Obrigações',
            'messages' => 'Mensagens',
            'templates' => 'Modelos',
            'settings' => 'Configurações',
        ];

        foreach ($modules as $slug => $title) {
            $response = $this->get(route('modules.show', $slug));

            $response->assertOk();
            $response->assertSee($title);
            $response->assertSee('Este módulo será implementado em uma próxima etapa.');
        }
    }

    public function test_unknown_module_returns_not_found(): void
    {
        $this->get('/modules/billing')->assertNotFound();
    }
}
