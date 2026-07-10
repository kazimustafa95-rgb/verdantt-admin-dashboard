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
class Ingredient extends Model
{
    use RestoresFromApiOnly;

    public $timestamps = false;

    protected $guarded = [];

    // 'lifespan' and 'aliases' arrive as already-decoded arrays from the API
    // response, so they're intentionally left uncast — Eloquent's `array`
    // cast expects a JSON string to decode and errors on a native array.
    protected $casts = [
        'is_produce' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function fromApi(array $item): static
    {
        return static::hydrate([$item])->first();
    }

    public static function findFromApi(int|string $id): ?static
    {
        $item = collect(app(VerdanttApiClient::class)->get('/admin/ingredients')->json('data') ?? [])
            ->first(fn (array $i) => (string) $i['id'] === (string) $id);

        return $item ? static::fromApi($item) : null;
    }
}
