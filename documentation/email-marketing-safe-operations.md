# Email Marketing Safe Operations

## Backup/export plan

Before any campaign repair, lead restoration, or schema migration, stop the local PHP and MariaDB processes only after confirming their PIDs, then export the CRM database to a timestamped file outside the web root:

```powershell
& 'C:\Program Files\MariaDB 12.3\bin\mariadb-dump.exe' -u root --single-transaction --routines --events u679665170_crm > .\writable\backups\u679665170_crm-YYYYMMDD-HHMMSS.sql
```

Verify the export is non-empty and contains the Email Marketing tables before making changes. Keep a second copy outside the project workspace. Do not place credentials in the command, repository, logs, or reports.

## Current safety rules

- Test mode stays enabled.
- The approved test-email list controls test delivery.
- Only one test recipient can be sent at a time.
- Suppressed, non-consented, inactive, and Do Not Contact records are excluded.
- Deleted leads are report-only until a separately approved restoration plan exists.
- Campaign recovery actions are admin-only and recorded in `rise_email_campaign_status_history`.
