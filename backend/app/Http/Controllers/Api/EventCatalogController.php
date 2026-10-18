<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventCategory;
use App\Models\EventPackage;
use Illuminate\Http\JsonResponse;

class EventCatalogController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = EventCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar kategori event',
            'data' => $categories,
        ]);
    }

    public function packages(): JsonResponse
    {
        $packages = EventPackage::query()
            ->with('category:id,name,slug')
            ->where('is_active', true)
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar paket event',
            'data' => $packages,
        ]);
    }

    public function showPackage(int $id): JsonResponse
    {
        $package = EventPackage::query()
            ->with('category:id,name,slug')
            ->where('is_active', true)
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail paket event',
            'data' => $package,
        ]);
    }
}
