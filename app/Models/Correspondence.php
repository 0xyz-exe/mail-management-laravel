<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Correspondence extends Model
{
    protected $fillable=['reference','department_id','category_id','created_by','assigned_to','direction','sender','recipient','subject','summary','received_at','sent_at','status','disposition','confidential'];
    protected $casts=['received_at'=>'date','sent_at'=>'date','confidential'=>'boolean'];
    public function department(){return $this->belongsTo(Department::class);}
    public function category(){return $this->belongsTo(MailCategory::class,'category_id');}
    public function creator(){return $this->belongsTo(User::class,'created_by');}
    public function assignee(){return $this->belongsTo(User::class,'assigned_to');}
    public function attachments(){return $this->hasMany(CorrespondenceAttachment::class);}
    public function audits(){return $this->hasMany(CorrespondenceAudit::class);}
}
