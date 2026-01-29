<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TankImage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'mosque_id',
        'uploaded_by',
        'image_path',
        'image_url',
        'description',
    ];

    /**
     * Get the mosque for this tank image.
     */
    public function mosque()
    {
        return $this->belongsTo(Mosque::class, 'mosque_id');
    }

    /**
     * Get the user who uploaded this tank image.
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

