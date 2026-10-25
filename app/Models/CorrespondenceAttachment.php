<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorrespondenceAttachment extends Model
{
    protected $fillable=['correspondence_id','uploaded_by','path','original_name','mime_type','size'];
    public function correspondence(){return $this->belongsTo(Correspondence::class);}
}
