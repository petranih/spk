<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function rankings()
    {
        return $this->hasMany(Ranking::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isActive()
    {
        return $this->is_active && 
               now()->between($this->start_date, $this->end_date);
    }
}