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
            'Sniper Rifles' => ['All', 'AWP', 'SSG 08', 'G3SG1', 'SCAR-20'],
            'Rifles' => ['All', 'AK-47', 'M4A1-S', 'M4A4', 'FAMAS', 'Galil AR', 'AUG', 'SG 553'],
            'SMGs' => ['All', 'MAC-10', 'MP9', 'MP7', 'MP5-SD', 'UMP-45', 'P90', 'PP-Bizon'],
            'Pistols' => ['All', 'Glock-18', 'P2000', 'USP-S', 'P250', 'Five-SeveN', 'Tec-9', 'CZ75-Auto', 'Desert Eagle', 'Dual Berettas', 'R8 Revolver'],
            'Shotguns' => ['All', 'Nova', 'XM1014', 'MAG-7', 'Sawed-Off'],
            'Machine Guns' => ['All', 'M249', 'Negev'],
            'Knives' => ['All'],
            'Gloves' => ['All'],
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

        $searchTerm = $search !== '' ? $search : ($weapon !== 'All' ? $weapon : null);
        $cacheKey = 'store.skins.cs2.'.Str::slug($type).'.'.Str::slug($weapon).'.'.Str::slug($searchTerm ?? 'all').'.p'.$page.'.pp'.$perPage;

        $skins = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($type, $searchTerm, $page, $perPage) {
            $response = Http::timeout(10)->get('https://kolzex.com/api/public/skins', [
                'game' => 'cs2',
                'type' => $type,
                'search' => $searchTerm,
                'wear' => 'Factory New',
                'per_page' => $perPage,
                'page' => $page,
            ]);

            if (! $response->ok()) {
                return [];
            }

            $payload = $response->json();
            $pageItems = $this->extractItems($payload);
            $items = [];

            foreach ($pageItems as $item) {
                $name = $this->extractName($item);
                $image = $this->extractImage($item);

                if (! $name || ! $image) {
                    continue;
                }

                $items[] = [
                    'name' => $name,
                    'image' => $image,
                ];
            }

            return collect($items)
                ->unique('name')
                ->values()
                ->all();
        });

        return response()->json([
            'success' => true,
            'data' => $skins,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'count' => count($skins),
                'has_more' => count($skins) >= $perPage,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function extractItems(array $payload): array
    {
        if (isset($payload['data']['items']) && is_array($payload['data']['items'])) {
            return $payload['data']['items'];
        }

        if (isset($payload['data']['data']) && is_array($payload['data']['data'])) {
            return $payload['data']['data'];
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        if (isset($payload['items']) && is_array($payload['items'])) {
            return $payload['items'];
        }

        return [];
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