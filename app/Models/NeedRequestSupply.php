<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NeedRequestSupply extends Model
{
    use HasFactory;

    protected $table = 'need_request_supplies';

    protected $fillable = [
        'need_request_id',
        'product_type',
        'requested_quantity',
    ];

    public function needRequest()
    {
        return $this->belongsTo(NeedRequest::class, 'need_request_id');
    }
}


