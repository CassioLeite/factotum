<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use App\Services\ContactService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(private ContactService $contacts) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        if (mb_strlen($search) > 100) {
            $search = mb_substr($search, 0, 100);
        }

        if ($search === '') {
            $search = null;
        }

        return view('contacts.index', [
            'contacts' => $this->contacts->paginate($search),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('contacts.create');
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $this->contacts->create($request->validated());

        return to_route('contacts.index')->with('success', 'Contato cadastrado.');
    }

    public function show(Contact $contact): View
    {
        return view('contacts.show', [
            'contact' => $contact,
        ]);
    }

    public function edit(Contact $contact): View
    {
        return view('contacts.edit', [
            'contact' => $contact,
        ]);
    }

    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $this->contacts->update($contact, $request->validated());

        return to_route('contacts.index')->with('success', 'Contato atualizado.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        if (! $this->contacts->delete($contact)) {
            return back()->with('error', 'Este contato possui obrigações e não pode ser excluído.');
        }

        return to_route('contacts.index')->with('success', 'Contato excluído.');
    }
}
