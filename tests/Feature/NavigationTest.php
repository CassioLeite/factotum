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
        $response->assertSee('Gerenciar obrigações');
        $response->assertSee('Gerenciar modelos');
        $response->assertDontSee("Let's get started");
        $response->assertDontSee('Campanhas');
    }

    public function test_obligations_open_the_listing_instead_of_the_placeholder(): void
    {
        $this->get(route('obligations.index'))
            ->assertOk()
            ->assertSee('Nova obrigação')
            ->assertDontSee('Este módulo será implementado em uma próxima etapa.');
    }

    public function test_templates_open_the_listing_instead_of_the_placeholder(): void
    {
        $this->get(route('templates.index'))
            ->assertOk()
            ->assertSee('Novo modelo')
            ->assertDontSee('Este módulo será implementado em uma próxima etapa.');
    }

    public function test_future_modules_show_a_pending_page(): void
    {
        $modules = [
            'messages' => 'Mensagens',
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
        $this->get('/modules/obligations')->assertNotFound();
        $this->get('/modules/templates')->assertNotFound();
    }
}
