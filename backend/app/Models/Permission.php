<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Un permiso es un par (action, subject), igual que una regla de CASL.
 */
#[Fillable(['action', 'subject', 'label'])]
class Permission extends Model
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function key(): string
    {
        return "{$this->action}:{$this->subject}";
    }
}
