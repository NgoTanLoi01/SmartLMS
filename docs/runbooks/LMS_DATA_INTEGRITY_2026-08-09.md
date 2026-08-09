# Runbook: LMS data integrity and legacy submissions

Migration: `2026_08_09_120000_consolidate_lms_data_integrity.php`

## Business rules confirmed from the active code

- `class_user`: one membership per `(class_id, user_id)`.
- `class_course`: one course assignment per `(class_id, course_id)`.
- `attendance_data`: one current value per `(attendance_column_id, user_id)`.
- `assignment_submissions`: one current submission per `(assignment_id, user_id)`. A resubmission updates that row; this is not an attempt-history table.
- `assignment_submissions` is the active source of truth. `submissions` is legacy and is retained as an empty compatibility table for now.

## Required production deployment sequence

1. Create and verify a database backup/checkpoint outside Laravel.
2. Put the application in maintenance mode and stop queue/scheduler writers.
3. Run the duplicate and legacy counts below and save the result with the deployment record.
4. Run `php artisan migrate --force`.
5. Verify all duplicate-group counts are zero and the four unique indexes exist.
6. Verify `lms_integrity_backups` contains one immutable snapshot for every merged, removed, or promoted legacy row.
7. Smoke-test student submit/resubmit, teacher review/grade, attendance save, class enrollment, and class-course assignment.
8. Resume workers and leave maintenance mode.

The maintenance window is required because application writes do not participate in the migration transaction. If a write races between dedupe and `ALTER TABLE`, creation of the unique index will fail rather than silently accept another duplicate; rerun only after confirming the backup table and duplicate counts.

## Read-only preflight queries

```sql
SELECT class_id, user_id, COUNT(*) AS duplicate_count
FROM class_user GROUP BY class_id, user_id HAVING COUNT(*) > 1;

SELECT class_id, course_id, COUNT(*) AS duplicate_count
FROM class_course GROUP BY class_id, course_id HAVING COUNT(*) > 1;

SELECT attendance_column_id, user_id, COUNT(*) AS duplicate_count
FROM attendance_data GROUP BY attendance_column_id, user_id HAVING COUNT(*) > 1;

SELECT assignment_id, user_id, COUNT(*) AS duplicate_count
FROM assignment_submissions GROUP BY assignment_id, user_id HAVING COUNT(*) > 1;

SELECT COUNT(*) AS legacy_submission_count FROM submissions;
```

## Merge rules

- Membership and class-course duplicates keep the lowest `id`; the duplicate rows are snapshotted before removal.
- Attendance keeps the row with the latest `updated_at`, then highest `id`.
- Assignment submission keeps the latest `updated_at`, then `submitted_at`, then highest `id`. Missing values on that canonical row are filled only from older duplicates; conflicting non-empty values use the latest canonical value. Every removed row and the pre-merge canonical row are snapshotted.
- A final, non-deleted legacy submission is promoted only when no active submission exists for that student and assignment. Existing active data always wins. Draft/deleted legacy rows are archived only and are never resurrected as current submissions.
- Files referenced by deduplicated rows are not deleted from storage. Their paths remain in `lms_integrity_backups.snapshot` for manual recovery.

## Rollback behavior

Rollback drops the four unique indexes and restores removed rows plus legacy rows from `lms_integrity_backups`. A submission promoted to the active table is deliberately not deleted during rollback, because it may have been graded or resubmitted after deployment. This favors no data loss over recreating the exact pre-migration duplication state.

Do not drop `submissions` in this release. Remove it only in a later migration after production verification confirms it remains empty and no external reporting/integration reads it.
