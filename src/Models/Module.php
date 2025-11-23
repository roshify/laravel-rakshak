<?php

namespace Roshify\LaravelRakshak\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $guarded = ['id'];

    protected $guard_name = 'web';
}
