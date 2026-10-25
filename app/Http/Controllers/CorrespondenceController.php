<?php

namespace App\Http\Controllers;

use App\Models\Correspondence;
use App\Models\CorrespondenceAudit;
use App\Models\CorrespondenceAttachment;
use App\Models\MailCategory;
use App\Models\User;
use App\Services\CorrespondenceRegistrar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CorrespondenceController extends Controller
{
    private function scope(Request $request) { $query=Correspondence::query(); if($request->user()->role==='admin') return $query; return $query->where('department_id',$request->user()->department_id)->where(fn($q)=>$q->where('confidential',false)->orWhere('created_by',$request->user()->id)->orWhere('assigned_to',$request->user()->id)); }
    private function authorizeRecord(Request $request, Correspondence $record): void { abort_unless($request->user()->role==='admin'||$request->user()->department_id===$record->department_id,404); }
    public function index(Request $request) { if($request->user()->role!=='admin'&&!$request->user()->department_id)return view('correspondence.unassigned'); $records=$this->scope($request)->with('category','assignee')->when($request->filled('q'),fn($q)=>$q->where(fn($x)=>$x->where('reference','like','%'.$request->string('q').'%')->orWhere('subject','like','%'.$request->string('q').'%')->orWhere('sender','like','%'.$request->string('q').'%')->orWhere('recipient','like','%'.$request->string('q').'%')))->when($request->filled('direction'),fn($q)=>$q->where('direction',$request->string('direction')))->when($request->filled('status'),fn($q)=>$q->where('status',$request->string('status')))->when($request->filled('category_id'),fn($q)=>$q->where('category_id',$request->integer('category_id')))->latest()->paginate(20)->withQueryString(); return view('correspondence.index',compact('records')); }
    public function create() { abort_unless(auth()->user()->role==='admin'||auth()->user()->department_id,403); return view('correspondence.create',['categories'=>MailCategory::orderBy('name')->get(),'staff'=>User::where('department_id',auth()->user()->department_id)->orderBy('name')->get()]); }
    public function store(Request $request,CorrespondenceRegistrar $registrar) { abort_unless($request->user()->department_id,403); $data=$request->validate(['direction'=>'required|in:incoming,outgoing','sender'=>'required|string|max:255','recipient'=>'required|string|max:255','subject'=>'required|string|max:255','summary'=>'nullable|string|max:10000','category_id'=>'nullable|exists:mail_categories,id','assigned_to'=>['nullable','integer',Rule::exists('users','id')->where('department_id',$request->user()->department_id)],'received_at'=>'nullable|date','sent_at'=>'nullable|date','confidential'=>'sometimes|boolean','attachments'=>'nullable|array|max:5','attachments.*'=>'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,txt']); $files=$request->file('attachments',[]);unset($data['attachments']);$record=$registrar->register($request->user(),$data,$files);return redirect()->route('correspondence.show',$record)->with('status','Correspondence registered. Reference: '.$record->reference); }
    public function show(Request $request,int $id) { $record=$this->scope($request)->with(['category','creator','assignee','attachments','audits.user'])->findOrFail($id);if($record->confidential&&$request->user()->role!=='admin'&&$record->created_by!==$request->user()->id&&$record->assigned_to!==$request->user()->id)abort(404);return view('correspondence.show',compact('record')); }
    public function update(Request $request,int $id) { $record=$this->scope($request)->findOrFail($id);$this->authorizeRecord($request,$record);$data=$request->validate(['status'=>'required|in:registered,processing,dispatched,completed,archived','disposition'=>'nullable|string|max:10000','assigned_to'=>['nullable','integer',Rule::exists('users','id')->where('department_id',$record->department_id)]]);DB::transaction(function()use($request,$record,$data){$before=['status'=>$record->status,'disposition'=>$record->disposition,'assigned_to'=>$record->assigned_to];$record->update($data);CorrespondenceAudit::create(['correspondence_id'=>$record->id,'user_id'=>$request->user()->id,'action'=>'updated','details'=>['before'=>$before,'after'=>$data]]);});return back()->with('status','Record updated.'); }
    public function download(Request $request,int $id,int $attachment) { $record=$this->scope($request)->with('attachments')->findOrFail($id);if($record->confidential&&$request->user()->role!=='admin'&&$record->created_by!==$request->user()->id&&$record->assigned_to!==$request->user()->id)abort(404);$file=$record->attachments->firstWhere('id',$attachment);abort_unless($file,404);return Storage::disk('correspondence')->download($file->path,$file->original_name); }
    public function report(Request $request) { abort_unless($request->user()->role==='admin'||$request->user()->role==='manager',403);$summary=$this->scope($request)->select('direction','status',DB::raw('count(*) as total'))->groupBy('direction','status')->orderBy('direction')->get();return view('correspondence.report',compact('summary')); }
}
