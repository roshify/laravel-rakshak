<?php

namespace Roshify\LaravelRakshak\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

class ModelHasRole extends MorphPivot
{
    protected $table = 'model_has_roles';
    
    protected $guarded = ['id'];

    protected $guard_name = 'web';
}
