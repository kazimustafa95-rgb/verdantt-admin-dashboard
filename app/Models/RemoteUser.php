<?php

namespace App\Models;

use App\Models\Concerns\RestoresFromApiOnly;
use App\Services\VerdanttApiClient;
use Illuminate\Database\Eloquent\Model;

/**
 * Not persisted anywhere — a plain in-memory data carrier hydrated directly
 * from the Verdantt API response for a single request, purely so Filament's
 * table component has a Model instance to bind to.
 */
class RemoteUser extends Model
{
    use RestoresFromApiOnly;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
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
            $response = $client->get('/admin/users', ['page' => $page, 'limit' => $limit]);
            $users = $response->json('data.users') ?? [];
            $totalPages = $response->json('data.meta.totalPages') ?? 1;

            foreach ($users as $item) {
                if ((string) $item['id'] === (string) $id) {
                    return static::fromApi($item);
                }
            }

            $page++;
        } while ($page <= $totalPages);

        return null;
    }
}
