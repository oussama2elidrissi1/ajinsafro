<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAirlineRequest;
use App\Http\Requests\UpdateAirlineRequest;
use App\Models\Airline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AirlineController extends Controller
{
    public function index(): View
    {
        $airlines = Airline::query()->orderBy('name')->paginate(20);
        return view('admin.circuits.airlines.index', compact('airlines'));
    }

    public function create(): View
    {
        return view('admin.circuits.airlines.create');
    }

    public function store(StoreAirlineRequest $request): RedirectResponse
    {
        $data = [
            'name' => $request->input('name'),
            'code_iata' => $request->input('iata_code'),
            'logo_path' => $request->input('logo_url'),
            'is_active' => $request->boolean('is_active', true),
        ];
        Airline::create($data);
        return redirect()
            ->route('admin.circuits.airlines.index')
            ->with('success', 'Compagnie aérienne créée avec succès.');
    }

    public function edit(Airline $airline): View
    {
        return view('admin.circuits.airlines.edit', compact('airline'));
    }

    public function update(UpdateAirlineRequest $request, Airline $airline): RedirectResponse
    {
        $data = [
            'name' => $request->input('name'),
            'code_iata' => $request->input('iata_code'),
            'logo_path' => $request->input('logo_url'),
            'is_active' => $request->boolean('is_active', true),
        ];
        $airline->update($data);
        return redirect()
            ->route('admin.circuits.airlines.index')
            ->with('success', 'Compagnie aérienne mise à jour.');
    }

    public function destroy(Airline $airline): RedirectResponse
    {
        $airline->delete();
        return redirect()
            ->route('admin.circuits.airlines.index')
            ->with('success', 'Compagnie aérienne supprimée.');
    }

    public function ajaxList(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $query = Airline::query()->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code_iata', 'like', '%' . $search . '%');
            });
        }

        $airlines = $query->get()->map(fn (Airline $airline) => $this->serializeAirline($airline));

        return response()->json([
            'success' => true,
            'message' => 'Liste des compagnies chargée.',
            'data' => $airlines,
            'errors' => (object) [],
        ]);
    }

    public function ajaxStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:airlines,name',
            'iata_code' => 'nullable|string|max:10',
            'logo_url' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'data' => null,
                'errors' => $validator->errors(),
            ], 422);
        }

        $airline = Airline::create([
            'name' => $request->input('name'),
            'code_iata' => $request->input('iata_code'),
            'logo_path' => $request->input('logo_url'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Compagnie aérienne créée avec succès.',
            'data' => $this->serializeAirline($airline),
            'errors' => (object) [],
        ]);
    }

    public function ajaxUpdate(Request $request, Airline $airline): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:airlines,name,' . $airline->id,
            'iata_code' => 'nullable|string|max:10',
            'logo_url' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'data' => null,
                'errors' => $validator->errors(),
            ], 422);
        }

        $airline->update([
            'name' => $request->input('name'),
            'code_iata' => $request->input('iata_code'),
            'logo_path' => $request->input('logo_url'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Compagnie aérienne mise à jour.',
            'data' => $this->serializeAirline($airline->fresh()),
            'errors' => (object) [],
        ]);
    }

    public function ajaxDestroy(Airline $airline): JsonResponse
    {
        $deletedId = $airline->id;
        $airline->delete();

        return response()->json([
            'success' => true,
            'message' => 'Compagnie aérienne supprimée.',
            'data' => ['id' => $deletedId],
            'errors' => (object) [],
        ]);
    }

    private function serializeAirline(Airline $airline): array
    {
        return [
            'id' => $airline->id,
            'name' => $airline->name,
            'code_iata' => $airline->code_iata,
            'logo_url' => $airline->logo_path,
            'is_active' => (bool) $airline->is_active,
        ];
    }
}
