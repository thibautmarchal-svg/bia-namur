<?php

namespace App\Models\Concerns;

use App\Models\City;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait pour les modèles métier multi-tenant Bia Namur.
 *
 * Pose la relation `city()` et permet d'appliquer un global scope
 * via la résolution d'une City courante (ex: depuis un middleware
 * lisant le slug de l'URL `/{citySlug}/...`).
 *
 * Au lancement (S1) une seule city existe : `namur`.
 * Mons, Liège, Tournai s'ajoutent sans refacto.
 */
trait BelongsToCity
{
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function scopeForCity($query, City|int $city)
    {
        $cityId = $city instanceof City ? $city->id : $city;

        return $query->where("{$this->getTable()}.city_id", $cityId);
    }
}
