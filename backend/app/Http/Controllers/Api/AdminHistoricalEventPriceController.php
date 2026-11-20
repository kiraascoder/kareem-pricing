<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistoricalEventPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminHistoricalEventPriceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'jenis_acara' => 'nullable|string|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|in:10,20,50,100',
        ]);
        $query = HistoricalEventPrice::query()
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('id_pemesanan', 'like', "%{$search}%")
                        ->orWhere('jenis_acara', 'like', "%{$search}%");
                });
            })
            ->when($filters['jenis_acara'] ?? null, fn ($q, $v) => $q->where('jenis_acara', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('tanggal_pelaksanaan', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('tanggal_pelaksanaan', '<=', $v));

        return response()->json(['success' => true, 'data' => $query
            ->orderByDesc('tanggal_pelaksanaan')->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 10))]);
    }

    public function categories(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => HistoricalEventPrice::query()
            ->select('jenis_acara')->distinct()->orderBy('jenis_acara')
            ->pluck('jenis_acara')]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['id_pemesanan'] = 'MAN-' . Str::upper((string) Str::ulid());
        $record = HistoricalEventPrice::create($this->withDateFeatures($data));
        return response()->json(['success' => true, 'message' => 'Data historis ditambahkan.', 'data' => $record], 201);
    }

    public function update(Request $request, HistoricalEventPrice $historicalEventPrice): JsonResponse
    {
        if (!$this->editable($historicalEventPrice)) {
            return response()->json(['success' => false, 'message' => 'Data dataset penelitian terkunci.'], 403);
        }
        $historicalEventPrice->update($this->withDateFeatures($this->validated($request)));
        return response()->json(['success' => true, 'message' => 'Data historis diperbarui.', 'data' => $historicalEventPrice->fresh()]);
    }

    public function destroy(HistoricalEventPrice $historicalEventPrice): JsonResponse
    {
        if (!$this->editable($historicalEventPrice)) {
            return response()->json(['success' => false, 'message' => 'Data dataset penelitian terkunci.'], 403);
        }
        $historicalEventPrice->delete();
        return response()->json(['success' => true, 'message' => 'Data historis dihapus.']);
    }

    private function editable(HistoricalEventPrice $record): bool
    {
        return str_starts_with((string) $record->id_pemesanan, 'MAN-');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'jenis_acara' => 'required|string|max:100',
            'tanggal_pelaksanaan' => 'required|date|before_or_equal:today',
            'harga_estimasi' => 'required|numeric|gt:0|decimal:0,2',
        ]);
    }

    private function withDateFeatures(array $data): array
    {
        $date = Carbon::parse($data['tanggal_pelaksanaan']);
        $data['tahun'] = (int) $date->year;
        $data['bulan'] = (int) $date->month;
        $data['hari_dalam_minggu'] = [
            'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'
        ][$date->dayOfWeek];
        $data['kategori_hari'] = $date->isWeekend() ? 'Akhir Pekan' : 'Hari Kerja';
        return $data;
    }
}
