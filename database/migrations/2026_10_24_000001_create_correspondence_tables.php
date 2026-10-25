<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  Schema::create('departments',function(Blueprint $t){$t->id();$t->string('name')->unique();$t->timestamps();});
  Schema::table('users',function(Blueprint $t){$t->string('role')->default('staff');$t->foreignId('department_id')->nullable()->constrained()->nullOnDelete();});
  Schema::create('mail_categories',function(Blueprint $t){$t->id();$t->string('name')->unique();$t->timestamps();});
  Schema::create('reference_sequences',function(Blueprint $t){$t->string('period',6)->primary();$t->unsignedInteger('next_value')->default(1);});
  Schema::create('correspondences',function(Blueprint $t){$t->id();$t->string('reference')->unique();$t->foreignId('department_id')->constrained()->restrictOnDelete();$t->foreignId('category_id')->nullable()->constrained('mail_categories')->nullOnDelete();$t->foreignId('created_by')->constrained('users')->restrictOnDelete();$t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();$t->string('direction');$t->string('sender');$t->string('recipient');$t->string('subject');$t->text('summary')->nullable();$t->date('received_at')->nullable();$t->date('sent_at')->nullable();$t->string('status')->default('registered');$t->text('disposition')->nullable();$t->boolean('confidential')->default(false);$t->timestamps();$t->index(['department_id','direction','status']);});
  Schema::create('correspondence_attachments',function(Blueprint $t){$t->id();$t->foreignId('correspondence_id')->constrained()->cascadeOnDelete();$t->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();$t->string('path');$t->string('original_name');$t->string('mime_type');$t->unsignedInteger('size');$t->timestamps();});
  Schema::create('correspondence_audits',function(Blueprint $t){$t->id();$t->foreignId('correspondence_id')->constrained()->cascadeOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->string('action');$t->json('details')->nullable();$t->timestamps();});
 }
 public function down(): void {Schema::dropIfExists('correspondence_audits');Schema::dropIfExists('correspondence_attachments');Schema::dropIfExists('correspondences');Schema::dropIfExists('reference_sequences');Schema::dropIfExists('mail_categories');Schema::table('users',function(Blueprint $t){$t->dropConstrainedForeignId('department_id');$t->dropColumn('role');});Schema::dropIfExists('departments');}
};
