<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    /**
     * @return array<string, array<int, string>>
     */
    private function weaponMap(): array
    {
        return [
            'All' => ['All'],
            'Pistols' => ['All', 'Glock-18', 'USP-S', 'P2000', 'Desert Eagle', 'P250', 'Tec-9', 'Five-SeveN', 'CZ75-Auto', 'Dual Berettas', 'R8 Revolver'],
            'Heavy' => ['All', 'Nova', 'XM1014', 'Sawed-Off', 'MAG-7', 'M249', 'Negev'],
            'SMGs' => ['All', 'MAC-10', 'MP9', 'MP7', 'MP5-SD', 'UMP-45', 'P90', 'PP-Bizon'],
            'Rifles' => ['All', 'AK-47', 'M4A4', 'M4A1-S', 'Galil AR', 'FAMAS', 'SG 553', 'AUG', 'AWP', 'SSG 08', 'SCAR-20', 'G3SG1'],
            'Knives' => ['All'],
            'Gloves' => ['All'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function categoryMap(): array
    {
        return [
            'Pistols' => 'Pistols',
            'SMGs' => 'SMGs',
            'Rifles' => 'Rifles',
            'Sniper Rifles' => 'Rifles',
            'Shotguns' => 'Heavy',
            'Machine Guns' => 'Heavy',
            'Heavy' => 'Heavy',
            'Knives' => 'Knives',
            'Gloves' => 'Gloves',
        ];
    }

    public function index()
    {
        $types = array_keys($this->weaponMap());
        $weapons = $this->weaponMap();

        return view('store', [
            'types' => $types,
            'weapons' => $weapons,
        ]);
    }

    public function skins(Request $request): JsonResponse
    {
        $types = array_keys($this->weaponMap());
        $type = $request->string('type')->trim()->value();
        $weapon = $request->string('weapon')->trim()->value();
        $search = $request->string('search')->trim()->value();
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(40, max(8, (int) $request->input('per_page', 16)));

        if (! in_array($type, $types, true)) {
            return response()->json(['success' => false, 'message' => 'Tipo no valido.'], 422);
        }

        $allowedWeapons = $this->weaponMap()[$type] ?? ['All'];
        if (! in_array($weapon, $allowedWeapons, true)) {
            $weapon = 'All';
        }

        $allSkins = $this->loadSkinsJson();

        $filtered = collect($allSkins)->filter(function ($item) use ($type, $weapon, $search) {
            $name = $item['name'] ?? '';
            $weaponName = $item['weapon'] ?? '';
            $typeName = $item['type'] ?? 'Other';

            if ($search !== '' && ! Str::contains(Str::lower($name), Str::lower($search))) {
                return false;
            }

            if ($type !== 'All' && $typeName !== $type) {
                return false;
            }

            if ($weapon !== 'All' && $weaponName !== $weapon) {
                return false;
            }

            return true;
        })->values();

        $total = $filtered->count();
        $offset = ($page - 1) * $perPage;
        $paginated = $filtered->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => $paginated,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'count' => $paginated->count(),
                'total' => $total,
                'has_more' => $total > $page * $perPage,
            ],
        ]);
    }

    /**
     * @return array<int, array{name: string, image: string, weapon: string, type: string}>
     */
    private function loadSkinsJson(): array
    {
        $cacheKey = 'store.skins.cs2.local';

        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            $path = storage_path('app/store/skins.json');
            $maxAgeSeconds = 60 * 60 * 24;

            if (! file_exists($path) || (time() - filemtime($path)) > $maxAgeSeconds) {
                $this->fetchSkinsFromApi();
            }

            if (! file_exists($path)) {
                return [];
            }

            $contents = file_get_contents($path);
            $data = json_decode($contents ?: '[]', true);

            return is_array($data) ? $data : [];
        });
    }

    /**
     * @return array<int, array{name: string, image: string, weapon: string, type: string}>
     */
    private function fetchSkinsFromApi(): array
    {
        $path = storage_path('app/store/skins.json');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $response = Http::timeout(20)->get('https://raw.githubusercontent.com/ByMykel/CSGO-API/main/public/api/en/skins.json');

        if (! $response->ok()) {
            return [];
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return [];
        }

        $items = [];

        foreach ($payload as $item) {
            if (! is_array($item)) {
                continue;
            }

            $rawName = $this->extractName($item);
            $image = $this->extractImage($item);
            $weaponName = $item['weapon']['name'] ?? null;
            $categoryName = $item['category']['name'] ?? null;

            if (! $rawName || ! $image || ! $weaponName || ! $categoryName) {
                continue;
            }

            $type = $this->categoryMap()[$categoryName] ?? 'Other';

            $displayName = $rawName;
            $key = $weaponName.'|'.$displayName;

            if (! isset($items[$key])) {
                $items[$key] = [
                    'name' => $displayName,
                    'image' => $image,
                    'weapon' => $weaponName,
                    'type' => $type,
                ];
            }
        }

        $normalized = array_values($items);

        file_put_contents($path, json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        Cache::forget('store.skins.cs2.local');

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function extractName(array $item): ?string
    {
        return $item['name']
            ?? $item['market_hash_name']
            ?? $item['market_name']
            ?? $item['title']
            ?? null;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function extractImage(array $item): ?string
    {
        return $item['image']
            ?? $item['image_url']
            ?? $item['icon']
            ?? $item['icon_url']
            ?? $item['imageUrl']
            ?? null;
    }
}