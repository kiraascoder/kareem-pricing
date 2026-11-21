<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminEventCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => EventCategory::query()
                ->withCount('packages')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('event_categories', 'name'),
            ],
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['slug'] = Str::slug($data['name']);

        if (EventCategory::where('slug', $data['slug'])->exists()) {
            throw ValidationException::withMessages([
                'name' => ['Slug kategori sudah digunakan.'],
            ]);
        }

        $category = EventCategory::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil ditambahkan.',
            'data' => $category,
        ], 201);
    }

    public function show(EventCategory $category): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $category->loadCount('packages'),
        ]);
    }

    public function update(
        Request $request,
        EventCategory $category
    ): JsonResponse {
        $data = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('event_categories', 'name')
                    ->ignore($category->id),
            ],
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if (array_key_exists('name', $data)) {
            $slug = Str::slug($data['name']);

            if (
                EventCategory::where('slug', $slug)
                ->whereKeyNot($category->id)
                ->exists()
            ) {
                throw ValidationException::withMessages([
                    'name' => ['Slug kategori sudah digunakan.'],
                ]);
            }

            $data['slug'] = $slug;
        }

        $category->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui.',
            'data' => $category->refresh()->loadCount('packages'),
        ]);
    }

    public function destroy(EventCategory $category): JsonResponse
    {
        $category->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dinonaktifkan.',
        ]);
    }
}
