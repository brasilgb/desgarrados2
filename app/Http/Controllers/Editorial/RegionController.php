<?php

namespace App\Http\Controllers\Editorial;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Models\Region;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegionController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('manage', Publication::class);
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $regions = Region::query()->with(['municipalities:id,name'])->withCount(['municipalities', 'publications'])
            ->when($data['q'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('editorial/regions', ['regions' => $regions, 'q' => $data['q'] ?? '']);
    }

    public function save(Request $request, ?Region $region = null): RedirectResponse
    {
        Gate::authorize('manage', Publication::class);
        $region ??= new Region;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'], 'slug' => ['nullable', 'string', 'max:160'],
            'kind' => ['required', Rule::in(['cultural', 'geographic'])], 'description' => ['nullable', 'string', 'max:5000'],
            'active' => ['required', 'boolean'], 'municipality_ids' => ['sometimes', 'array'],
            'municipality_ids.*' => ['integer', 'distinct', 'exists:municipalities,id'],
        ]);
        $slug = Str::slug($data['slug'] ?? ($region->exists ? $region->slug : $data['name']));
        validator(['slug' => $slug], ['slug' => ['required', 'max:160', Rule::unique('regions')->ignore($region->id)]])->validate();
        try {
            DB::transaction(function () use ($region, $data, $slug): void {
                $region->fill([...$data, 'slug' => $slug])->save();
                if (array_key_exists('municipality_ids', $data)) {
                    $region->municipalities()->sync($data['municipality_ids']);
                }
            }, 3);
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw ValidationException::withMessages(['slug' => 'Slug já utilizado.']);
            }
            throw $e;
        }

        return back();
    }
}
