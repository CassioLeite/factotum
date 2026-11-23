<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageTemplateRequest;
use App\Http\Requests\UpdateMessageTemplateRequest;
use App\Models\MessageTemplate;
use App\Services\MessageTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
    public function __construct(private MessageTemplateService $templates) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        if (mb_strlen($search) > 100) {
            $search = mb_substr($search, 0, 100);
        }

        if ($search === '') {
            $search = null;
        }

        return view('templates.index', [
            'templates' => $this->templates->paginate($search),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('templates.create');
    }

    public function store(StoreMessageTemplateRequest $request): RedirectResponse
    {
        if ($request->input('intent') === 'preview') {
            return $this->previewResponse($request);
        }

        $this->templates->create($request->validated());

        return to_route('templates.index')->with('success', 'Modelo cadastrado.');
    }

    public function show(MessageTemplate $template): View
    {
        return view('templates.show', [
            'template' => $template,
            'preview' => $this->templates->preview($template->body),
        ]);
    }

    public function edit(MessageTemplate $template): View
    {
        return view('templates.edit', [
            'template' => $template,
        ]);
    }

    public function update(UpdateMessageTemplateRequest $request, MessageTemplate $template): RedirectResponse
    {
        if ($request->input('intent') === 'preview') {
            return $this->previewResponse($request);
        }

        $this->templates->update($template, $request->validated());

        return to_route('templates.index')->with('success', 'Modelo atualizado.');
    }

    public function destroy(MessageTemplate $template): RedirectResponse
    {
        if (! $this->templates->delete($template)) {
            return back()->with('error', 'O modelo padrão não pode ser excluído.');
        }

        return to_route('templates.index')->with('success', 'Modelo excluído.');
    }

    private function previewResponse(StoreMessageTemplateRequest $request): RedirectResponse
    {
        return back()
            ->withInput()
            ->with('preview', $this->templates->preview((string) $request->validated('body')));
    }
}
