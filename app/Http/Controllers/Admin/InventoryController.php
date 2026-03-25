<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ItemCondition;
use App\Enums\ItemStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Item::query()->with('category')->latest();

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        if ($request->filled('condition')) {
            $query->where('condition', (string) $request->input('condition'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        $items = $query->paginate(15)->withQueryString();

        return view('admin.inventory.index', [
            'items' => $items,
            'categories' => Category::query()->orderBy('name')->get(),
            'conditions' => ItemCondition::cases(),
            'statuses' => ItemStatus::cases(),
            'filters' => $request->only(['search', 'category_id', 'condition', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.inventory.form', [
            'item' => new Item(),
            'categories' => Category::query()->orderBy('name')->get(),
            'conditions' => ItemCondition::cases(),
            'statuses' => ItemStatus::cases(),
            'isEditing' => false,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'condition' => ['required', Rule::in(ItemCondition::values())],
            'tags' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'status' => ['required', Rule::in(ItemStatus::values())],
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('inventory', 'public');
        }

        unset($validated['image']);

        $validated['slug'] = $this->makeUniqueSlug($validated['name']);
        $validated['reserved_quantity'] = 0;
        $validated['tags'] = $this->normalizeTags($request->input('tags'));

        Item::query()->create($validated);

        return redirect()
            ->route('admin.inventory.index')
            ->with('status', 'Item added to inventory.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item): View
    {
        return view('admin.inventory.show', [
            'item' => $item->load('category'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Item $item): View
    {
        return view('admin.inventory.form', [
            'item' => $item,
            'categories' => Category::query()->orderBy('name')->get(),
            'conditions' => ItemCondition::cases(),
            'statuses' => ItemStatus::cases(),
            'isEditing' => true,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Item $item): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'condition' => ['required', Rule::in(ItemCondition::values())],
            'tags' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_image' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(ItemStatus::values())],
        ]);

        if ((int) $validated['quantity'] < $item->reserved_quantity) {
            return back()
                ->withInput()
                ->withErrors(['quantity' => 'Quantity cannot be lower than the currently reserved quantity.']);
        }

        if ($request->boolean('remove_image') && $item->image_path !== null) {
            Storage::disk('public')->delete($item->image_path);
            $validated['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            $newImagePath = $request->file('image')->store('inventory', 'public');

            if ($item->image_path !== null) {
                Storage::disk('public')->delete($item->image_path);
            }

            $validated['image_path'] = $newImagePath;
        }

        unset($validated['image'], $validated['remove_image']);

        $validated['slug'] = $this->makeUniqueSlug($validated['name'], $item->id);
        $validated['tags'] = $this->normalizeTags($request->input('tags'));

        $item->update($validated);

        return redirect()
            ->route('admin.inventory.show', $item)
            ->with('status', 'Inventory item updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Item $item): RedirectResponse
    {
        $item->status = ItemStatus::ARCHIVED;
        $item->save();
        $item->delete();

        return redirect()
            ->route('admin.inventory.index')
            ->with('status', 'Inventory item archived.');
    }

    private function makeUniqueSlug(string $name, ?int $ignoreItemId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (
            Item::query()
                ->where('slug', $slug)
                ->when($ignoreItemId !== null, fn ($query) => $query->where('id', '!=', $ignoreItemId))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeTags(?string $rawTags): array
    {
        if ($rawTags === null || trim($rawTags) === '') {
            return [];
        }

        return Collection::make(explode(',', $rawTags))
            ->map(fn (string $tag) => trim($tag))
            ->filter(fn (string $tag) => $tag !== '')
            ->values()
            ->all();
    }
}
