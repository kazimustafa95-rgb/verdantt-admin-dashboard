<?php

namespace App\Models;

use App\Models\Concerns\RestoresFromApiOnly;
use App\Services\VerdanttApiClient;
use Illuminate\Database\Eloquent\Model;

/**
 * Not persisted anywhere — a plain in-memory data carrier hydrated directly
 * from the Verdantt API response for a single request, purely so Filament's
 * table/form components have a Model instance to bind to.
 */
class Recipe extends Model
{
    use RestoresFromApiOnly;

    public $timestamps = false;

    protected $guarded = [];

    // 'ingredients' arrives as an already-decoded array from the API response,
    // so it's intentionally left uncast — Eloquent's `array` cast expects a
    // JSON string to decode and errors on a native array.
    protected $casts = [
        'average_rating' => 'float',
        'created_at' => 'datetime',
    ];

    public static function fromApi(array $item): static
    {
        return static::hydrate([$item])->first();
    }

    public static function findFromApi(int|string $id): ?static
    {
        $client = app(VerdanttApiClient::class);
        $page = 1;
        $limit = 100;

        do {
            $response = $client->get('/admin/recipes', ['page' => $page, 'limit' => $limit]);
            $recipes = $response->json('data.recipes') ?? [];
            $totalPages = $response->json('data.meta.totalPages') ?? 1;

            foreach ($recipes as $item) {
                if ((string) $item['id'] === (string) $id) {
                    return static::fromApi($item);
                }
            }

            $page++;
        } while ($page <= $totalPages);

        return null;
    }
}
