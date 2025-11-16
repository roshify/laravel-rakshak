<?php

namespace Roshp\LaravelRakshak\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kblais\QueryFilter\Filterable;

class Role extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $guarded = ['id'];

    protected $guard_name = 'web';

    /**
     * Role cna be morphed by many models.
     */
    public function models()
    {
        return $this->morphedByMany('model', 'model', 'model_has_role', 'role_id', 'model_id')
            ->using(ModelHasRole::class)
            ->withTimestamps();
    }
}
