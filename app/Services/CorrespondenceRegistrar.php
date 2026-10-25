<?php

namespace App\Services;

use App\Models\Correspondence;
use App\Models\CorrespondenceAudit;
use App\Models\CorrespondenceAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CorrespondenceRegistrar
{
    public function register(User $actor, array $data, array $files = []): Correspondence
    {
        $paths=[];
        try {
            return DB::transaction(function () use ($actor,$data,$files,&$paths) {
                $period=now()->format('Ym');
                DB::table('reference_sequences')->insertOrIgnore(['period'=>$period,'next_value'=>1]);
                $sequence=DB::table('reference_sequences')->where('period',$period)->lockForUpdate()->first();
                $number=$sequence->next_value;
                DB::table('reference_sequences')->where('period',$period)->update(['next_value'=>$number+1]);
                $record=Correspondence::create([...$data,'department_id'=>$actor->department_id,'created_by'=>$actor->id,'reference'=>'COR-'.$period.'-'.str_pad((string)$number,6,'0',STR_PAD_LEFT)]);
                foreach ($files as $file) {
                    if (!$file instanceof UploadedFile) continue;
                    $path=$file->store($record->reference,'correspondence');
                    $paths[]=$path;
                    CorrespondenceAttachment::create(['correspondence_id'=>$record->id,'uploaded_by'=>$actor->id,'path'=>$path,'original_name'=>substr($file->getClientOriginalName(),0,255),'mime_type'=>$file->getMimeType(),'size'=>$file->getSize()]);
                }
                CorrespondenceAudit::create(['correspondence_id'=>$record->id,'user_id'=>$actor->id,'action'=>'registered','details'=>['reference'=>$record->reference]]);
                return $record;
            });
        } catch (\Throwable $e) {
            foreach ($paths as $path) Storage::disk('correspondence')->delete($path);
            throw $e;
        }
    }
}
