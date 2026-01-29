<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MosqueSupply extends Model
{
    use HasFactory;

    protected $table = 'mosque_supplies';

    protected $fillable = [
        'mosque_id',
        'product_type',
        'current_quantity',
        'required_quantity',
        'need_level',
        'need_score',
        'is_active',
    ];

    /**
     * Relationship: each supply belongs to one mosque
     */
    public function mosque()
    {
        return $this->belongsTo(Mosque::class);
    }
}
