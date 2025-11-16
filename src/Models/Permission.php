<?php

namespace Roshp\LaravelRakshak\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];

    protected $guard_name = 'web';

    public function model(): MorphTo
    {
        return $this->morphTo('model');
    }
}
