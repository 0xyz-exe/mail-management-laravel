<?php

namespace Database\Seeders;

use App\Models\Correspondence;
use App\Models\CorrespondenceAttachment;
use App\Models\CorrespondenceAudit;
use App\Models\Department;
use App\Models\MailCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $deptAdmin = Department::firstOrCreate(['name' => 'Administration']);
        $deptFin = Department::firstOrCreate(['name' => 'Finance']);
        $deptOps = Department::firstOrCreate(['name' => 'Operations']);

        $catGeneral = MailCategory::firstOrCreate(['name' => 'General']);
        $catFin = MailCategory::firstOrCreate(['name' => 'Finance']);
        $catLegal = MailCategory::firstOrCreate(['name' => 'Legal']);
        $catHR = MailCategory::firstOrCreate(['name' => 'HR']);
        $catProc = MailCategory::firstOrCreate(['name' => 'Procurement']);

        if (app()->environment('testing')) {
            return;
        }

        // 1. Users
        $admin = User::updateOrCreate(
            ['email' => 'admin@demo.test'],
            [
                'name' => 'Alexander Wright',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->role = 'admin';
        $admin->department_id = $deptAdmin->id;
        $admin->save();

        $staffHendra = User::firstOrCreate(
            ['email' => 'hendra.k@corp.test'],
            ['name' => 'Hendra Kurniawan', 'password' => Hash::make('password')]
        );
        $staffHendra->role = 'staff';
        $staffHendra->department_id = $deptAdmin->id;
        $staffHendra->save();

        $staffDewi = User::firstOrCreate(
            ['email' => 'dewi.l@corp.test'],
            ['name' => 'Dewi Lestari', 'password' => Hash::make('password')]
        );
        $staffDewi->role = 'staff';
        $staffDewi->department_id = $deptFin->id;
        $staffDewi->save();

        $staffMarcus = User::firstOrCreate(
            ['email' => 'marcus.v@corp.test'],
            ['name' => 'Marcus Vance', 'password' => Hash::make('password')]
        );
        $staffMarcus->role = 'manager';
        $staffMarcus->department_id = $deptOps->id;
        $staffMarcus->save();

        // 2. Correspondences
        // Inbound 1
        $c1 = Correspondence::firstOrCreate(['reference' => 'COR-202610-000101'], [
            'direction' => 'incoming',
            'department_id' => $deptAdmin->id,
            'category_id' => $catLegal->id,
            'created_by' => $admin->id,
            'assigned_to' => $staffHendra->id,
            'sender' => 'Kementerian Komunikasi dan Informatika RI (Kominfo)',
            'recipient' => 'Head of Corporate Legal & Compliance',
            'subject' => 'Pemberitahuan Audit Kepatuhan Keamanan Sistem Elektronik & PSE Lingkup Privat 2026',
            'summary' => 'Permintaan kelengkapan dokumen sertifikasi ISO/IEC 27001:2022 dan laporan uji penetrasi keamanan siber berkala untuk pembaruan izin operasional sistem elektronik.',
            'received_at' => Carbon::now()->subDays(2)->toDateString(),
            'status' => 'processing',
            'disposition' => 'Diteruskan kepada tim IT Security dan Legal untuk menyiapkan berkas audit sebelum tanggal 15 November 2026.',
            'confidential' => false,
            'created_at' => Carbon::now()->subDays(2),
        ]);
        if ($c1->wasRecentlyCreated) {
            CorrespondenceAudit::create([
                'correspondence_id' => $c1->id,
                'user_id' => $admin->id,
                'action' => 'registered',
                'details' => ['reference' => $c1->reference],
                'created_at' => Carbon::now()->subDays(2),
            ]);
            CorrespondenceAudit::create([
                'correspondence_id' => $c1->id,
                'user_id' => $admin->id,
                'action' => 'updated',
                'details' => ['status' => 'processing', 'assigned_to' => $staffHendra->id],
                'created_at' => Carbon::now()->subDay(),
            ]);

            // Fake attachment file on storage
            $fakePath = "{$c1->reference}/Surat_Pemberitahuan_Audit_Kominfo_2026.pdf";
            Storage::disk('correspondence')->put($fakePath, "%PDF-1.4 Mock Audit Notice Content");
            CorrespondenceAttachment::create([
                'correspondence_id' => $c1->id,
                'uploaded_by' => $admin->id,
                'path' => $fakePath,
                'original_name' => 'Surat_Pemberitahuan_Audit_Kominfo_2026.pdf',
                'mime_type' => 'application/pdf',
                'size' => 245760,
                'created_at' => Carbon::now()->subDays(2),
            ]);
        }

        // Inbound 2
        $c2 = Correspondence::firstOrCreate(['reference' => 'COR-202610-000102'], [
            'direction' => 'incoming',
            'department_id' => $deptFin->id,
            'category_id' => $catFin->id,
            'created_by' => $staffDewi->id,
            'assigned_to' => $staffDewi->id,
            'sender' => 'Kantor Pelayanan Pajak (KPP) Pratama Setiabudi',
            'recipient' => 'Finance & Accounting Director',
            'subject' => 'Surat Permintaan Penjelasan atas Data dan Keterangan (SP2DK) Tahun Pajak 2025',
            'summary' => 'Klarifikasi rekonsiliasi faktur pajak masukan dan bukti potong PPh Pasal 23 kuartal IV 2025.',
            'received_at' => Carbon::now()->subDay()->toDateString(),
            'status' => 'registered',
            'disposition' => 'Lakukan rekonsiliasi GL dengan data e-Faktur dan susun draf tanggapan resmi.',
            'confidential' => false,
            'created_at' => Carbon::now()->subDay(),
        ]);
        if ($c2->wasRecentlyCreated) {
            CorrespondenceAudit::create(['correspondence_id' => $c2->id, 'user_id' => $staffDewi->id, 'action' => 'registered', 'details' => ['reference' => $c2->reference]]);
        }

        // Outbound 1
        $c3 = Correspondence::firstOrCreate(['reference' => 'COR-202610-000103'], [
            'direction' => 'outgoing',
            'department_id' => $deptAdmin->id,
            'category_id' => $catProc->id,
            'created_by' => $admin->id,
            'assigned_to' => $staffHendra->id,
            'sender' => 'Head of Procurement & Vendor Relations',
            'recipient' => 'PT Telkom Indonesia (Persero) Tbk - Enterprise Division',
            'subject' => 'Surat Pengajuan Perpanjangan Kontrak Dedicated Leased Line 10 Gbps Cikarang',
            'summary' => 'Pengajuan addendum perpanjangan SLA jaringan 99.99% dan upgrade interkoneksi link sekunder periode 2026-2028.',
            'sent_at' => Carbon::now()->toDateString(),
            'status' => 'dispatched',
            'disposition' => 'Telah dikirimkan via kurir tercatat dan email korporat resmi.',
            'confidential' => false,
            'created_at' => Carbon::now(),
        ]);
        if ($c3->wasRecentlyCreated) {
            CorrespondenceAudit::create(['correspondence_id' => $c3->id, 'user_id' => $admin->id, 'action' => 'registered', 'details' => ['reference' => $c3->reference]]);
            CorrespondenceAudit::create(['correspondence_id' => $c3->id, 'user_id' => $staffHendra->id, 'action' => 'updated', 'details' => ['status' => 'dispatched']]);
        }

        // Outbound 2
        $c4 = Correspondence::firstOrCreate(['reference' => 'COR-202610-000104'], [
            'direction' => 'outgoing',
            'department_id' => $deptOps->id,
            'category_id' => $catGeneral->id,
            'created_by' => $staffMarcus->id,
            'assigned_to' => $staffMarcus->id,
            'sender' => 'VP Operations & Facilities',
            'recipient' => 'Pengelola Gedung SCBD Equity Tower',
            'subject' => 'Pemberitahuan Jadwal Maintenance Server Rack & Penggantian UPS Baterai Bank',
            'summary' => 'Permohonan izin akses teknisi dan izin bongkar muat peralatan hardware pada akhir pekan.',
            'sent_at' => Carbon::now()->subDays(3)->toDateString(),
            'status' => 'completed',
            'disposition' => 'Izin akses telah disetujui pihak pengelola gedung.',
            'confidential' => false,
            'created_at' => Carbon::now()->subDays(3),
        ]);
        if ($c4->wasRecentlyCreated) {
            CorrespondenceAudit::create(['correspondence_id' => $c4->id, 'user_id' => $staffMarcus->id, 'action' => 'registered', 'details' => ['reference' => $c4->reference]]);
        }

        // Inbound 3
        $c5 = Correspondence::firstOrCreate(['reference' => 'COR-202610-000105'], [
            'direction' => 'incoming',
            'department_id' => $deptAdmin->id,
            'category_id' => $catHR->id,
            'created_by' => $admin->id,
            'assigned_to' => null,
            'sender' => 'BPJS Ketenagakerjaan Kantor Cabang Jakarta Sudirman',
            'recipient' => 'People & Culture Department',
            'subject' => 'Rekonsiliasi Data Iuran JHT, JKK, JKM, dan Jaminan Pensiun Karyawan Baru',
            'summary' => 'Daftar validasi kepesertaan 12 tenaga kerja baru dan panduan pelaporan SIPP online.',
            'received_at' => Carbon::now()->toDateString(),
            'status' => 'registered',
            'disposition' => null,
            'confidential' => false,
            'created_at' => Carbon::now(),
        ]);
        if ($c5->wasRecentlyCreated) {
            CorrespondenceAudit::create(['correspondence_id' => $c5->id, 'user_id' => $admin->id, 'action' => 'registered', 'details' => ['reference' => $c5->reference]]);
        }
    }
}
