# Mail Management Laravel

Laravel correspondence register for incoming/outgoing mail, department routing, private attachments, search, dispositions, audit history, and printable records.

## Setup (Windows PowerShell)

```powershell
Copy-Item .env.example .env
php C:/Users/wondr/.config/herd-lite/bin/composer.phar install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Register a user at `/register`. No user accounts or private correspondence are seeded. A trusted administrator must bootstrap one registered account and assign its department in Tinker:

```powershell
php artisan tinker
```

```php
$user = App\Models\User::where('email', 'admin@example.com')->firstOrFail();
$dept = App\Models\Department::where('name', 'Administration')->firstOrFail();
$user->forceFill(['role' => 'admin', 'department_id' => $dept->id])->save();
```

Assign staff roles/departments explicitly via trusted Tinker/database operations. New accounts default to `staff` with no department and cannot access correspondence until assigned. Admin/manager report authorization is enforced server-side. External email is not sent.

## Security and behavior

- Reference format: `COR-YYYYMM-######`; monthly sequence increments under a database row lock in the same transaction as the correspondence insert. The unique reference constraint is the final collision guard. Use a transactional relational production database; SQLite is for local/testing only.
- Attachments: up to 5 files, 10 MB each; PDF, JPG/PNG, Word, Excel, or TXT allowlist. Persisted on the private `correspondence` disk, not public web storage. Downloads are authenticated and re-check department and confidential-record authorization.
- Confidential records: visible only to creator, assigned staff member, or admin; applies to lists, details, and downloads. Standard records are department-scoped; cross-department requests return 404.
- Registration and disposition/status/assignment changes record audit events. This is not tamper-proof storage.
- Seeded department/category names are examples only; no private business records included. Registration cannot assign department or elevated roles.

## Validation

```powershell
php artisan migrate:fresh --seed
php artisan test --compact
npm run build
php C:/Users/wondr/.config/herd-lite/bin/composer.phar validate --no-check-publish
php C:/Users/wondr/.config/herd-lite/bin/composer.phar audit
npm audit --omit=dev
```

For production, configure HTTPS, private storage permissions/backups, mail for account recovery, least-privilege access, retention rules, and a supported transactional database. Do not commit `.env`, personal data, `vendor`, `node_modules`, or build output. MIT license.
