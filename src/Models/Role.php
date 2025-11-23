<?php

namespace Roshify\LaravelRakshak\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

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
