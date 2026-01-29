<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NeedRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'mosque_id',
        'requested_by',
        'water_quantity',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'water_quantity' => 'integer',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the mosque for this need request.
     */
    public function mosque()
    {
        return $this->belongsTo(Mosque::class, 'mosque_id');
    }

    /**
     * Get the user who requested this need request.
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who approved this need request.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the deliveries for this need request.
     */
    public function deliveries()
    {
        return $this->hasMany(Delivery::class, 'need_request_id');
    }

    /**
     * Get the supplies requested in this need request.
     */
    public function supplies()
    {
        return $this->hasMany(NeedRequestSupply::class, 'need_request_id');
    }
}

