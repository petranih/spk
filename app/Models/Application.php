<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period_id',
        'application_number',
        'full_name',
        'nisn',
        'school',
        'class',
        'birth_date',
        'birth_place',
        'gender',
        'address',
        'phone',
        'status',
        'notes',
        'final_score',
        'rank',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'final_score' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Alias untuk user relationship - untuk compatibility dengan kode yang menggunakan student
    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function applicationValues()
    {
        return $this->hasMany(ApplicationValue::class);
    }

    public function documents()
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function validation()
    {
        return $this->hasOne(Validation::class);
    }

    public function ranking()
    {
        return $this->hasOne(Ranking::class);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($application) {
            if (!$application->application_number) {
                $application->application_number = 'APP-' . 
                    $application->period_id . '-' . 
                    str_pad(Application::where('period_id', $application->period_id)->count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}