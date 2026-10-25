<?php

use App\Http\Controllers\CorrespondenceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/',fn()=>redirect()->route('correspondence.index'));
Route::middleware('auth')->group(function(){
 Route::get('/dashboard',fn()=>redirect()->route('correspondence.index'))->name('dashboard');
 Route::get('/correspondence',[CorrespondenceController::class,'index'])->name('correspondence.index');
 Route::get('/correspondence/create',[CorrespondenceController::class,'create'])->name('correspondence.create');
 Route::post('/correspondence',[CorrespondenceController::class,'store'])->name('correspondence.store');
 Route::get('/correspondence/{id}',[CorrespondenceController::class,'show'])->whereNumber('id')->name('correspondence.show');
 Route::patch('/correspondence/{id}',[CorrespondenceController::class,'update'])->whereNumber('id')->name('correspondence.update');
 Route::get('/correspondence/{id}/attachments/{attachment}',[CorrespondenceController::class,'download'])->whereNumber('id')->whereNumber('attachment')->name('correspondence.attachments.download');
 Route::get('/reports',[CorrespondenceController::class,'report'])->name('correspondence.report');
 Route::get('/profile',[ProfileController::class,'edit'])->name('profile.edit');Route::patch('/profile',[ProfileController::class,'update'])->name('profile.update');Route::delete('/profile',[ProfileController::class,'destroy'])->name('profile.destroy');
});
require __DIR__.'/auth.php';
