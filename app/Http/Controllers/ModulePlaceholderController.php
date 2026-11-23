<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ModulePlaceholderController extends Controller
{
    public function __invoke(string $module): View
    {
        $titles = [
            'obligations' => 'Obrigações',
            'messages' => 'Mensagens',
            'templates' => 'Modelos',
            'settings' => 'Configurações',
        ];

        abort_unless(isset($titles[$module]), 404);

        return view('modules.placeholder', [
            'title' => $titles[$module],
        ]);
    }
}
