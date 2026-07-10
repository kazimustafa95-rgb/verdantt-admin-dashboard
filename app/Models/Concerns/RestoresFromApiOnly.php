<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Livewire re-fetches any hydrated Eloquent model bound to a component
 * property using a real database query on every subsequent request (see
 * Livewire\Features\SupportModels\ModelSynth). Since these models have no
 * real table, that query would always fail — so we intercept it here and
 * resolve from the API instead via the model's own `findFromApi()`.
 */
trait RestoresFromApiOnly
{
    public function newQueryForRestoration($ids)
    {
        $id = is_array($ids) ? ($ids[0] ?? null) : $ids;
        $modelClass = static::class;

        return new class($id, $modelClass) {
            public function __construct(
                protected int|string|null $id,
                protected string $modelClass,
            ) {
            }

            public function useWritePdo(): static
            {
                return $this;
            }

            public function firstOrFail(): object
            {
                $record = $this->id !== null ? $this->modelClass::findFromApi($this->id) : null;

                if (! $record) {
                    throw (new ModelNotFoundException())->setModel($this->modelClass, [$this->id]);
                }

                return $record;
            }
        };
    }
}
