<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\MosqueNeedScoreService;

class Mosque extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'location',
        'latitude',
        'longitude',
        'capacity',
        'current_water_level',
        'required_water_level',
        'need_level',
        'need_score',
        'description',
        'mosque_admin_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'capacity' => 'integer',
        'current_water_level' => 'integer',
        'required_water_level' => 'integer',
        'need_score' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the admin user for this mosque.
     */
    public function mosqueAdmin()
    {
        return $this->belongsTo(User::class, 'mosque_admin_id');
    }

    /**
     * Get the donations for this mosque.
     */
    public function donations()
    {
        return $this->hasMany(Donation::class, 'mosque_id');
    }

    /**
     * Get the need requests for this mosque.
     */
    public function needRequests()
    {
        return $this->hasMany(NeedRequest::class, 'mosque_id');
    }

    /**
     * Get the tank images for this mosque.
     */
    public function tankImages()
    {
        return $this->hasMany(TankImage::class, 'mosque_id');
    }

    /**
     * Get the deliveries for this mosque.
     */
    public function deliveries()
    {
        return $this->hasMany(Delivery::class, 'mosque_id');
    }

    public function supplies()
    {
        return $this->hasMany(MosqueSupply::class, 'mosque_id');
    }

    /**
     * Boot method to recalculate need score when deliveries are updated.
     */
    protected static function boot()
    {
        parent::boot();

        // Recalculate need score when mosque is updated
        static::updated(function ($mosque) {
            // Only recalculate if water level related fields changed
            if ($mosque->isDirty(['current_water_level', 'required_water_level'])) {
                $needScoreService = app(MosqueNeedScoreService::class);
                $needScoreService->updateNeedLevel($mosque);
            }
        });
    }
}
