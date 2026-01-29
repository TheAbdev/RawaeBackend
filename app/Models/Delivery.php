<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\MosqueNeedScoreService;

class Delivery extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'truck_id',
        'mosque_id',
        'need_request_id',
        'liters_delivered',
        'delivery_latitude',
        'delivery_longitude',
        'proof_image_path',
        'proof_image_url',
        'status',
        'expected_delivery_date',
        'actual_delivery_date',
        'delivered_by',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'liters_delivered' => 'integer',
        'delivery_latitude' => 'decimal:8',
        'delivery_longitude' => 'decimal:8',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'datetime',
    ];

    /**
     * Get the truck for this delivery.
     */
    public function truck()
    {
        return $this->belongsTo(Truck::class, 'truck_id');
    }

    /**
     * Get the mosque for this delivery.
     */
    public function mosque()
    {
        return $this->belongsTo(Mosque::class, 'mosque_id');
    }

    /**
     * Get the need request for this delivery.
     */
    public function needRequest()
    {
        return $this->belongsTo(NeedRequest::class, 'need_request_id');
    }

    /**
     * Get the user who delivered this delivery.
     */
    public function deliverer()
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    /**
     * Boot method to recalculate mosque need score when delivery status changes.
     */
    protected static function boot()
    {
        parent::boot();

        // Recalculate need score when delivery is updated (especially when status changes to delivered)
        static::updated(function ($delivery) {
            if ($delivery->isDirty('status') && $delivery->status === 'delivered') {
                $mosque = $delivery->mosque;
                if ($mosque) {
                    $needScoreService = app(MosqueNeedScoreService::class);
                    $needScoreService->updateNeedLevel($mosque);
                }
            }
        });
    }
}

