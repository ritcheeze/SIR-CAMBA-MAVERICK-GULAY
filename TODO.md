# TODO - Debug & Backend Precision

## Completed
- [x] Read core backend PHP files and Flask `app.py` to locate runtime error sources.
- [x] Inspect DB schema (`lspu.sql`) for unique keys used by `ON DUPLICATE KEY UPDATE`.
- [x] Identify corrupted file: `enrollment.php` contains concatenated unrelated HTML (login/register block appended).

## Next
- [ ] Fix corrupted `enrollment.php` by removing the appended login/register HTML content.
- [ ] Smoke test flow after fix: login → enrollment (AJAX + submit) → dashboard → drop subject.
- [ ] Admin flow test: approve enrollment → evaluate grades → delete student.
- [ ] Precision hardening:
  - [ ] Improve server-side validation and numeric bounds for enrollment/admin grade inputs.
  - [ ] Improve DB error handling/logging around transactions.
  - [ ] Add missing-table/column friendly error messages (e.g., `sections` table existence).
- [ ] Confirm deployment mode (PHP vs Flask) to avoid auth/session mismatches.

