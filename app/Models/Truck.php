<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Truck extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'truck_id',
        'name',
        'capacity',
        'status',
        'current_latitude',
        'current_longitude',
        'last_location_update',
        'assigned_driver_id',
        'driver_name',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'capacity' => 'integer',
        'current_latitude' => 'decimal:8',
        'current_longitude' => 'decimal:8',
        'last_location_update' => 'datetime',
    ];

    /**
     * Get the driver assigned to this truck.
     */
    public function driver()
    {
        return $this->belongsTo(User::class, 'assigned_driver_id');
    }

    /**
     * Get the deliveries for this truck.
     */
    public function deliveries()
    {
        return $this->hasMany(Delivery::class, 'truck_id');
    }
}

