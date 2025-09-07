<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Period extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'is_active',
        'max_applications',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Scope untuk periode aktif
     */
    public function scopeActive($query)
    {
        $now = Carbon::now()->toDateString();
        
        return $query->where('is_active', true)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
    }

    /**
     * Scope untuk periode yang tersedia untuk pendaftaran
     */
    public function scopeAvailable($query)
    {
        $now = Carbon::now()->toDateString();
        
        return $query->where('is_active', true)
                    ->where('end_date', '>=', $now);
    }

    /**
     * Scope untuk periode yang akan datang
     */
    public function scopeUpcoming($query)
    {
        $now = Carbon::now()->toDateString();
        
        return $query->where('is_active', true)
                    ->where('start_date', '>', $now);
    }

    /**
     * Check apakah periode sedang berlangsung
     */
    public function getIsOngoingAttribute()
    {
        $now = Carbon::now()->toDateString();
        
        return $this->is_active && 
               $this->start_date <= $now && 
               $this->end_date >= $now;
    }

    /**
     * Check apakah periode belum dimulai
     */
    public function getIsUpcomingAttribute()
    {
        $now = Carbon::now()->toDateString();
        
        return $this->is_active && $this->start_date > $now;
    }

    /**
     * Check apakah periode sudah berakhir
     */
    public function getIsExpiredAttribute()
    {
        $now = Carbon::now()->toDateString();
        
        return !$this->is_active || $this->end_date < $now;
    }

    /**
     * Get sisa hari untuk periode
     */
    public function getRemainingDaysAttribute()
    {
        $now = Carbon::now();
        
        if ($this->is_upcoming) {
            $days = $now->diffInDays($this->start_date);
            return $days . ' hari lagi dimulai';
        } elseif ($this->is_ongoing) {
            $days = $now->diffInDays($this->end_date);
            return $days . ' hari lagi berakhir';
        } else {
            return 'Sudah berakhir';
        }
    }

    /**
     * Relasi dengan applications
     */
    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Get jumlah aplikasi untuk periode ini
     */
    public function getApplicationsCountAttribute()
    {
        return $this->applications()->count();
    }

    /**
     * Check apakah periode masih bisa menerima aplikasi
     */
    public function canAcceptApplications()
    {
        if (!$this->is_ongoing) {
            return false;
        }

        if ($this->max_applications && $this->applications_count >= $this->max_applications) {
            return false;
        }

        return true;
    }
}