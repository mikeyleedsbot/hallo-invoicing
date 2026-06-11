<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait BelongsToUser
 *
 * Voegt automatische user-scoping toe aan een model.
 * - Global scope: queries tonen alleen records van de ingelogde user
 * - Creating event: user_id wordt automatisch gezet bij aanmaken
 * - user() relatie beschikbaar
 *
 * Gebruik: `use BelongsToUser;` in het model.
 */
trait BelongsToUser
{
    public static function bootBelongsToUser(): void
    {
        // Global scope: filter altijd op ingelogde user
        static::addGlobalScope('belongs_to_user', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where($builder->getModel()->getTable() . '.user_id', auth()->id());
            }
        });

        // Automatisch user_id zetten bij aanmaken
        static::creating(function ($model) {
            if (auth()->check() && !$model->user_id) {
                $model->user_id = auth()->id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope zonder user-filter (voor admin-doeleinden).
     */
    public function scopeWithoutUserScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('belongs_to_user');
    }
}
