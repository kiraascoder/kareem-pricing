<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminEventPackageController extends Controller
{
    public function index(): JsonResponse
    {
        $packages = EventPackage::query()
            ->with('category:id,name,slug')
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $packages,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => [
                'required',
                'integer',
                Rule::exists('event_categories', 'id')
                    ->where('is_active', true),
            ],
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'min_participants' => 'nullable|integer|min:1',
            'max_participants' => 'nullable|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        $this->validateParticipantRange($data);

        $package = EventPackage::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Paket event berhasil ditambahkan.',
            'data' => $package->load('category:id,name,slug'),
        ], 201);
    }

    public function show(EventPackage $package): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $package->load('category:id,name,slug'),
        ]);
    }

    public function update(
        Request $request,
        EventPackage $package
    ): JsonResponse {
        $data = $request->validate([
            'category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('event_categories', 'id')
                    ->where('is_active', true),
            ],
            'name' => 'sometimes|required|string|max:150',
            'description' => 'nullable|string',
            'base_price' => 'sometimes|required|numeric|min:0',
            'min_participants' => 'nullable|integer|min:1',
            'max_participants' => 'nullable|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        $this->validateParticipantRange(
            array_merge(
                $package->only([
                    'min_participants',
                    'max_participants',
                ]),
                $data
            )
        );

        $package->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Paket event berhasil diperbarui.',
            'data' => $package->refresh()
                ->load('category:id,name,slug'),
        ]);
    }

    public function destroy(EventPackage $package): JsonResponse
    {
        $package->update([
            'is_active' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paket event berhasil dinonaktifkan.',
        ]);
    }

    private function validateParticipantRange(array $data): void
    {
        $min = $data['min_participants'] ?? null;
        $max = $data['max_participants'] ?? null;

        if (
            $min !== null &&
            $max !== null &&
            (int) $max < (int) $min
        ) {
            throw ValidationException::withMessages([
                'max_participants' => [
                    'Maksimal peserta tidak boleh lebih kecil dari minimal peserta.',
                ],
            ]);
        }
    }
}
