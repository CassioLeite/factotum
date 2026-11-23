<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreObligationRequest;
use App\Http\Requests\UpdateObligationRequest;
use App\Models\Contact;
use App\Models\Obligation;
use App\Services\ObligationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ObligationController extends Controller
{
    public function __construct(private ObligationService $obligations) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        if (mb_strlen($search) > 100) {
            $search = mb_substr($search, 0, 100);
        }

        if ($search === '') {
            $search = null;
        }

        return view('obligations.index', [
            'obligations' => $this->obligations->paginate($search),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('obligations.create', [
            'contacts' => $this->contacts(),
        ]);
    }

    public function store(StoreObligationRequest $request): RedirectResponse
    {
        $this->obligations->create($request->validated());

        return to_route('obligations.index')->with('success', 'Obrigação cadastrada.');
    }

    public function show(Obligation $obligation): View
    {
        $obligation->load('contact');

        return view('obligations.show', [
            'obligation' => $obligation,
        ]);
    }

    public function edit(Obligation $obligation): View
    {
        return view('obligations.edit', [
            'obligation' => $obligation,
            'contacts' => $this->contacts(),
        ]);
    }

    public function update(UpdateObligationRequest $request, Obligation $obligation): RedirectResponse
    {
        $this->obligations->update($obligation, $request->validated());

        return to_route('obligations.index')->with('success', 'Obrigação atualizada.');
    }

    public function destroy(Obligation $obligation): RedirectResponse
    {
        $this->obligations->delete($obligation);

        return to_route('obligations.index')->with('success', 'Obrigação excluída.');
    }

    /**
     * @return Collection<int, Contact>
     */
    private function contacts(): Collection
    {
        return Contact::query()->orderBy('name')->orderBy('id')->get(['id', 'name']);
    }
}
