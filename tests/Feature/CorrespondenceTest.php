<?php

namespace Tests\Feature;

use App\Models\Correspondence;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CorrespondenceTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $department='Finance',string $role='staff'): User
    {
        $dept=Department::firstOrCreate(['name'=>$department]);$user=User::factory()->create();$user->forceFill(['department_id'=>$dept->id,'role'=>$role])->save();return $user;
    }

    private function record(User $user,array $extra=[]): Correspondence
    {
        $response=$this->actingAs($user)->post('/correspondence',['direction'=>'incoming','sender'=>'Acme Ltd','recipient'=>'Finance','subject'=>'Invoice review',...$extra]);
        $response->assertSessionHasNoErrors();return Correspondence::latest('id')->firstOrFail();
    }

    public function test_registration_assigns_unique_reference_and_writes_audit(): void
    {
        $staff=$this->staff();$one=$this->record($staff);$two=$this->record($staff,['subject'=>'Second']);
        $this->assertNotSame($one->reference,$two->reference);$this->assertStringStartsWith('COR-'.now()->format('Ym').'-',$one->reference);
        $this->assertDatabaseHas('correspondence_audits',['correspondence_id'=>$one->id,'action'=>'registered']);
    }

    public function test_department_scope_and_confidential_access_restrict_list_detail_and_download(): void
    {
        Storage::fake('correspondence');$owner=$this->staff('Finance');$same=$this->staff('Finance');$other=$this->staff('Legal');
        $normal=$this->record($owner);$secret=$this->record($owner,['subject'=>'Hidden confidential','confidential'=>1,'attachments'=>[UploadedFile::fake()->create('memo.pdf',20,'application/pdf')]]);
        $this->actingAs($same)->get('/correspondence')->assertOk()->assertSee($normal->reference)->assertDontSee($secret->reference);
        $this->actingAs($same)->get("/correspondence/{$secret->id}")->assertNotFound();
        $this->actingAs($same)->get("/correspondence/{$secret->id}/attachments/{$secret->attachments()->first()->id}")->assertNotFound();
        $this->actingAs($other)->get("/correspondence/{$normal->id}")->assertNotFound();
        $this->actingAs($owner)->get("/correspondence/{$secret->id}/attachments/{$secret->attachments()->first()->id}")->assertOk();
        Storage::disk('correspondence')->assertExists($secret->attachments()->first()->path);
    }

    public function test_invalid_file_type_is_rejected_and_disposition_update_is_audited(): void
    {
        $user=$this->staff();$record=$this->record($user);
        $this->actingAs($user)->patch("/correspondence/{$record->id}",['status'=>'processing','disposition'=>'Route to accounts'])->assertRedirect();
        $this->assertDatabaseHas('correspondence_audits',['correspondence_id'=>$record->id,'action'=>'updated']);
        $this->actingAs($user)->post('/correspondence',['direction'=>'incoming','sender'=>'A','recipient'=>'B','subject'=>'Bad file','attachments'=>[UploadedFile::fake()->create('payload.exe',10,'application/octet-stream')]])->assertSessionHasErrors('attachments.0');
    }

    public function test_reports_are_role_limited_and_registration_never_grants_roles(): void
    {
        $staff=$this->staff();$this->actingAs($staff)->get('/reports')->assertForbidden();
        $this->post('/logout');$this->post('/register',['name'=>'New staff','email'=>'newstaff@example.com','password'=>'Password!123','password_confirmation'=>'Password!123','role'=>'admin'])->assertRedirect();
        $this->assertSame('staff',User::where('email','newstaff@example.com')->firstOrFail()->role);
    }
}
