<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RequiresConfirmedAction;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use RequiresConfirmedAction;

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', Rule::in(['0', '1'])],
            'sort' => ['nullable', Rule::in(['latest', 'name_asc', 'name_desc', 'items_desc', 'items_asc'])],
        ]);

        $query = Category::query()
            ->withCount('items');

        if (!empty($validated['search'])) {
            $search = (string) $validated['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('is_active', $validated) && $validated['is_active'] !== null && $validated['is_active'] !== '') {
            $query->where('is_active', $validated['is_active'] === '1');
        }

        match ($validated['sort'] ?? 'latest') {
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'items_desc' => $query->orderByDesc('items_count'),
            'items_asc' => $query->orderBy('items_count'),
            default => $query->latest(),
        };

        return view('admin.categories.index', [
            'categories' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'is_active', 'sort']),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', [
            'category' => new Category(),
            'isEditing' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requireConfirmedAction($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', "regex:/^[\p{L}\p{M}][\p{L}\p{M}\s'.-]*$/u", 'unique:categories,name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.regex' => 'Category name must contain letters only. Spaces, apostrophes, periods, and hyphens are allowed.',
        ]);

        $validated['slug'] = $this->makeUniqueSlug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        Category::query()->create($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category created successfully.');
    }

    public function show(string $id): RedirectResponse
    {
        return redirect()->route('admin.categories.index');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
            'isEditing' => true,
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->requireConfirmedAction($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', "regex:/^[\p{L}\p{M}][\p{L}\p{M}\s'.-]*$/u", Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.regex' => 'Category name must contain letters only. Spaces, apostrophes, periods, and hyphens are allowed.',
        ]);

        $validated['slug'] = $this->makeUniqueSlug($validated['name'], $category->id);
        $validated['is_active'] = $request->boolean('is_active', true);

        $category->update($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->requireConfirmedAction(request());

        Item::withTrashed()
            ->where('category_id', $category->id)
            ->update(['category_id' => null]);

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category archived successfully.');
    }

    private function makeUniqueSlug(string $name, ?int $ignoreCategoryId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (
            Category::query()
                ->where('slug', $slug)
                ->when($ignoreCategoryId !== null, fn ($query) => $query->where('id', '!=', $ignoreCategoryId))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
