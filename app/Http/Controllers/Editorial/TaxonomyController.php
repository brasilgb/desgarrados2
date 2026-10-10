<?php

namespace App\Http\Controllers\Editorial;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Municipality;
use App\Models\Publication;
use App\Models\Region;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TaxonomyController extends Controller
{
    public function index(Request $request, string $kind): Response
    {
        Gate::authorize('manage', Publication::class);
        $class = $this->taxonomy($kind);
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        return Inertia::render('editorial/taxonomy', ['kind' => $kind,
            'items' => $class::query()->when($data['q'] ?? null, fn ($q, $search) => $q->where('name', 'like', '%'.$search.'%'))
                ->orderBy('name')->paginate(20)->withQueryString(), 'q' => $data['q'] ?? '']);
    }

    public function save(Request $request, string $kind, ?int $id = null): RedirectResponse
    {
        Gate::authorize('manage', Publication::class);
        $class = $this->taxonomy($kind);
        $item = $id ? $class::findOrFail($id) : new $class;
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'slug' => ['nullable', 'string', 'max:100']]);
        // Renaming without an explicit slug preserves the stable identifier.
        $slug = Str::slug($data['slug'] ?? ($item->exists ? $item->slug : $data['name']));
        validator(['slug' => $slug], ['slug' => ['required', 'max:100', Rule::unique($item->getTable())->ignore($item->id)]])->validate();
        try {
            $item->fill(['name' => $data['name'], 'slug' => $slug])->save();
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw ValidationException::withMessages(['slug' => 'Slug já utilizado.']);
            } throw $e;
        }

        return back();
    }

    public function delete(string $kind, int $id): RedirectResponse
    {
        Gate::authorize('manage', Publication::class);
        try {
            $this->taxonomy($kind)::findOrFail($id)->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1451) {
                throw ValidationException::withMessages(['taxonomy' => 'Remova os vínculos editoriais antes de excluir.']);
            } throw $e;
        }

        return back();
    }

    public function lookup(Request $request, string $kind): JsonResponse
    {
        Gate::authorize('viewAny', Publication::class);
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $class = match ($kind) {
            'categories' => Category::class, 'tags' => Tag::class, 'regions' => Region::class,
            'municipalities' => Municipality::class, 'authors' => User::class, default => abort(404)
        };

        return response()->json($class::query()->select('id', 'name')->when($data['q'] ?? null, fn ($q, $search) => $q->where('name', 'like', '%'.$search.'%'))->orderBy('name')->limit(20)->get());
    }

    /** @return class-string<Category>|class-string<Tag> */
    private function taxonomy(string $kind): string
    {
        return match ($kind) {
            'categories' => Category::class, 'tags' => Tag::class, default => abort(404)
        };
    }
}
