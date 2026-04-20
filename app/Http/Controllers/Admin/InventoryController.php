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
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'condition' => ['nullable', Rule::in(ItemCondition::values())],
            'status' => ['nullable', Rule::in(ItemStatus::values())],
            'sort' => ['nullable', Rule::in(['latest', 'name_asc', 'name_desc', 'price_asc', 'price_desc', 'quantity_asc', 'quantity_desc'])],
        ]);

        $query = Item::query()->withTrashed()->with('category');

        if (!empty($validated['search'])) {
            $search = (string) $validated['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (!empty($validated['category_id'])) {
            $query->where('category_id', (int) $validated['category_id']);
        }

        if (!empty($validated['condition'])) {
            $query->where('condition', (string) $validated['condition']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', (string) $validated['status']);
        }

        match ($validated['sort'] ?? 'latest') {
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'quantity_asc' => $query->orderBy('quantity'),
            'quantity_desc' => $query->orderByDesc('quantity'),
            default => $query->latest(),
        };

        $items = $query->paginate(15)->withQueryString();

        return view('admin.inventory.index', [
            'items' => $items,
            'categories' => Category::query()->orderBy('name')->get(),
            'conditions' => ItemCondition::cases(),
            'statuses' => ItemStatus::cases(),
            'filters' => $request->only(['search', 'category_id', 'condition', 'status', 'sort']),
            'archivedCount' => Item::onlyTrashed()->count() + Item::query()->where('status', ItemStatus::ARCHIVED)->count(),
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
            'seller_name' => ['required', 'string', 'max:255'],
            'seller_contact_number' => ['required', 'string', 'max:50'],
            'condition' => ['required', Rule::in(ItemCondition::values())],
            'tags' => ['nullable', 'string'],
            'image' => $this->imageRules(),
            'status' => ['required', Rule::in(ItemStatus::values())],
            'restock_at' => ['nullable', 'date'],
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('inventory', 'public');
        }

        unset($validated['image']);

        $validated['slug'] = $this->makeUniqueSlug($validated['name']);
        $validated['reserved_quantity'] = 0;
        $validated['tags'] = $this->normalizeTags($request->input('tags'));
        $validated['restock_at'] = $request->filled('restock_at') ? $request->input('restock_at') : null;

        Item::query()->create($validated);

        return redirect()
            ->route('admin.inventory.index')
            ->with('status', 'Item added to inventory.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $item): View
    {
        return view('admin.inventory.show', [
            'item' => $this->findAdminItemOrFail($item)->load('category'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $item): View
    {
        $itemModel = $this->findAdminItemOrFail($item);

        return view('admin.inventory.form', [
            'item' => $itemModel,
            'categories' => Category::query()->orderBy('name')->get(),
            'conditions' => ItemCondition::cases(),
            'statuses' => ItemStatus::cases(),
            'isEditing' => true,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $item): RedirectResponse
    {
        $itemModel = $this->findAdminItemOrFail($item);

        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'seller_name' => ['required', 'string', 'max:255'],
            'seller_contact_number' => ['required', 'string', 'max:50'],
            'condition' => ['required', Rule::in(ItemCondition::values())],
            'tags' => ['nullable', 'string'],
            'image' => $this->imageRules(),
            'remove_image' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(ItemStatus::values())],
            'restock_at' => ['nullable', 'date'],
        ]);

        if ((int) $validated['quantity'] < $itemModel->reserved_quantity) {
            return back()
                ->withInput()
                ->withErrors(['quantity' => 'Quantity cannot be lower than the currently reserved quantity.']);
        }

        if ($request->boolean('remove_image') && $itemModel->image_path !== null) {
            Storage::disk('public')->delete($itemModel->image_path);
            $validated['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            $newImagePath = $request->file('image')->store('inventory', 'public');

            if ($itemModel->image_path !== null) {
                Storage::disk('public')->delete($itemModel->image_path);
            }

            $validated['image_path'] = $newImagePath;
        }

        unset($validated['image'], $validated['remove_image']);

        $validated['slug'] = $this->makeUniqueSlug($validated['name'], $itemModel->id);
        $validated['tags'] = $this->normalizeTags($request->input('tags'));
        $validated['restock_at'] = $request->filled('restock_at') ? $request->input('restock_at') : null;

        $itemModel->update($validated);

        if ($itemModel->trashed()) {
            $itemModel->restore();
        }

        return redirect()
            ->route('admin.inventory.show', $itemModel)
            ->with('status', 'Inventory item updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $item): RedirectResponse
    {
        $itemModel = $this->findAdminItemOrFail($item);

        if ($itemModel->trashed()) {
            $itemModel->restore();
        }

        $itemModel->status = ItemStatus::ARCHIVED;
        $itemModel->save();

        return redirect()
            ->route('admin.inventory.index')
            ->with('status', 'Inventory item archived.');
    }

    public function unarchive(string $item): RedirectResponse
    {
        $itemModel = $this->findAdminItemOrFail($item);

        if ($itemModel->trashed()) {
            $itemModel->restore();
            $itemModel->refresh();
        }

        if ($itemModel->status === ItemStatus::ARCHIVED) {
            $targetStatus = $itemModel->availableQuantity() > 0
                ? ItemStatus::ACTIVE
                : ItemStatus::OUT_OF_STOCK;

            $itemModel->status = $targetStatus;
            $itemModel->save();
        }

        return redirect()
            ->back()
            ->with('status', 'Inventory item restored from archive.');
    }

    public function forceDestroy(string $item): RedirectResponse
    {
        $itemModel = $this->findAdminItemOrFail($item);

        if ($itemModel->reservationItems()->exists()) {
            return redirect()
                ->route('admin.inventory.show', $itemModel)
                ->withErrors([
                    'delete' => 'This item cannot be permanently deleted because it is linked to one or more reservations.',
                ]);
        }

        if ($itemModel->image_path !== null && $itemModel->image_path !== '') {
            Storage::disk('public')->delete($itemModel->image_path);
        }

        $itemModel->forceDelete();

        return redirect()
            ->route('admin.inventory.index')
            ->with('status', 'Inventory item permanently deleted.');
    }

    private function makeUniqueSlug(string $name, ?int $ignoreItemId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (
            Item::withTrashed()
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

    private function findAdminItemOrFail(string $slug): Item
    {
        return Item::withTrashed()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * @return array<int, mixed>
     */
    private function imageRules(): array
    {
        return [
            'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:3072',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null) {
                    return;
                }

                $dimensions = @getimagesize($value->getRealPath());

                if ($dimensions === false || ($dimensions[1] ?? 0) === 0) {
                    $fail('The image could not be read.');
                    return;
                }

                $width = (int) $dimensions[0];
                $height = (int) $dimensions[1];
                $ratio = $width / $height;
                $expectedRatio = 16 / 9;

                if (abs($ratio - $expectedRatio) > 0.01) {
                    $fail('The image must use a 16:9 aspect ratio.');
                }
            },
        ];
    }
}
