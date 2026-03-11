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
            'Melee' => ['All'],
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

        $usingSearch = $search !== '';
        $searchTerm = $usingSearch ? $search : ($weapon !== 'All' ? $weapon : null);
        $cacheKey = 'store.skins.cs2.'.Str::slug($type).'.'.Str::slug($weapon).'.'.Str::slug($searchTerm ?? 'all').'.p'.$page.'.pp'.$perPage;

        $skins = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($type, $weapon, $allowedWeapons, $searchTerm, $page, $perPage) {
            $items = [];

            // If a specific weapon is selected or search term is present, use API search directly.
            if ($searchTerm !== null) {
                $response = Http::timeout(10)->get('https://kolzex.com/api/public/skins', [
                    'game' => 'cs2',
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
                $items = $this->mapItems($pageItems);

                return collect($items)
                    ->unique('name')
                    ->values()
                    ->all();
            }

            // If type is All or Melee, just return API list without type filtering.
            if ($type === 'All' || $type === 'Melee') {
                $response = Http::timeout(10)->get('https://kolzex.com/api/public/skins', [
                    'game' => 'cs2',
                    'wear' => 'Factory New',
                    'per_page' => $perPage,
                    'page' => $page,
                ]);

                if (! $response->ok()) {
                    return [];
                }

                $payload = $response->json();
                $pageItems = $this->extractItems($payload);
                $items = $this->mapItems($pageItems);

                return collect($items)
                    ->unique('name')
                    ->values()
                    ->all();
            }

            // For grouped types, fetch more pages and filter by weapon list locally.
            $weaponList = array_values(array_filter($allowedWeapons, fn ($w) => $w !== 'All'));
            $maxPages = 8;

            for ($remotePage = 1; $remotePage <= $maxPages; $remotePage++) {
                $response = Http::timeout(10)->get('https://kolzex.com/api/public/skins', [
                    'game' => 'cs2',
                    'wear' => 'Factory New',
                    'per_page' => 60,
                    'page' => $remotePage,
                ]);

                if (! $response->ok()) {
                    break;
                }

                $payload = $response->json();
                $pageItems = $this->extractItems($payload);

                if (empty($pageItems)) {
                    break;
                }

                foreach ($this->mapItems($pageItems) as $item) {
                    foreach ($weaponList as $weaponName) {
                        if (Str::contains($item['name'], $weaponName)) {
                            $items[] = $item;
                            break;
                        }
                    }
                }

                if (count($items) >= ($page * $perPage)) {
                    break;
                }
            }

            $unique = collect($items)->unique('name')->values();
            $offset = ($page - 1) * $perPage;

            return $unique->slice($offset, $perPage)->values()->all();
        });

        return response()->json([
            'success' => true,
            'data' => $skins,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'count' => count($skins),
                'has_more' => count($skins) >= $perPage,
                'search_active' => $usingSearch,
            ],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $pageItems
     * @return array<int, array{name: string, image: string}>
     */
    private function mapItems(array $pageItems): array
    {
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

        return $items;
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