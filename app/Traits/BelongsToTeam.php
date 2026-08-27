<?php

namespace App\Traits;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait BelongsToTeam
 *
 * Voegt automatische team-scoping toe aan een model.
 * - Global scope: queries tonen alleen records van het huidige team
 * - Creating event: team_id wordt automatisch gezet bij aanmaken,
 *   user_id blijft staan als "wie heeft dit aangemaakt" (auteurschap)
 * - team()/user() relaties beschikbaar
 *
 * Gebruik: `use BelongsToTeam;` in het model.
 */
trait BelongsToTeam
{
    public static function bootBelongsToTeam(): void
    {
        static::addGlobalScope('belongs_to_team', function (Builder $builder) {
            if (auth()->check() && auth()->user()->currentTeam) {
                $builder->where($builder->getModel()->getTable() . '.team_id', auth()->user()->currentTeam->id);
            }
        });

        static::creating(function ($model) {
            if (auth()->check()) {
                if (!$model->team_id && auth()->user()->currentTeam) {
                    $model->team_id = auth()->user()->currentTeam->id;
                }
                if (!$model->user_id) {
                    $model->user_id = auth()->id();
                }
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope zonder team-filter (voor admin-doeleinden / achtergrondtaken).
     */
    public function scopeWithoutTeamScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('belongs_to_team');
    }
}
