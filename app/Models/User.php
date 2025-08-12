<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function validations()
    {
        return $this->hasMany(Validation::class, 'validator_id');
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isValidator()
    {
        return $this->role === 'validator';
    }

    public function isStudent()
    {
        return $this->role === 'student';
    }
}