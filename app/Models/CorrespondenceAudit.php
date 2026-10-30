<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorrespondenceAudit extends Model
{
    protected $fillable=['correspondence_id','user_id','action','details'];
    protected $casts=['details'=>'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
