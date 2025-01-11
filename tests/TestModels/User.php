<?php

namespace NickKlein\Habits\Tests\TestModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable 
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}
