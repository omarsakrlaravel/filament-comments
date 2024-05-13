<?php

namespace Parallax\FilamentComments\Models\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Parallax\FilamentComments\Models\FilamentComment;
use Filament\Facades\Filament;

trait HasFilamentComments
{
    public function filamentComments(): HasMany
    {
        $query = $this->hasMany(FilamentComment::class, 'subject_id')
                      ->where('subject_type', $this->getMorphClass())
                      ->latest();

        $tenant = Filament::getTenant();
        if ($tenant && $tenant->id) {
            $query->where('organization_id', $tenant->id);
        }

        return $query;
    }
}