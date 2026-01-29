<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'donor_id',
        'mosque_id',
        'amount',
        'payment_method',
        'payment_transaction_id',
        'status',
        'verified',
        'verified_by',
        'verified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the donor user for this donation.
     */
    public function donor()
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    /**
     * Get the mosque for this donation.
     */
    public function mosque()
    {
        return $this->belongsTo(Mosque::class, 'mosque_id');
    }

    /**
     * Get the user who verified this donation.
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}

