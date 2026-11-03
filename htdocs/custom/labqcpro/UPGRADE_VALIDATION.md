# LabQC Pro 1.1.1 — Upgrade validation

**Status:** Recommended deployment validation. This procedure has **not** yet been completed on a restored real Dolibarr database copy; do not present it as completed.

## Preconditions

- Use a separate staging Dolibarr instance, matching the existing production version and PHP environment.
- Make and verify a backup of its database, documents and `htdocs/custom/labqcpro/` folder.
- Start from **LabQC Pro 1.0.43**, with actual existing QC specifications, tests, audit entries and finalized approvals.
- Do not run a backup/restore or migration against the public demo or clients' production systems.
- Use a Dolibarr database account that can perform normal application operations.

## Baseline (before update)

Note actual counts (replace `llx_` with your DB prefix):

```sql
SELECT COUNT(*) AS specifications FROM llx_labqcpro_specification;
SELECT COUNT(*) AS analytical_parameters FROM llx_labqcpro_specification_line;
SELECT COUNT(*) AS qc_tests FROM llx_labqcpro_test;
SELECT COUNT(*) AS qc_results FROM llx_labqcpro_test_result;
SELECT COUNT(*) AS approvals FROM llx_labqcpro_test_approval;
SELECT COUNT(*) AS audit_events FROM llx_labqcpro_audit;
```

Record at least two reference IDs: one finalized PASS test with a CoA and one finalized FAIL/OOS test. Record the current seven LabQC rights assigned to Analyst and QC Manager.

## Upgrade test

1. Copy the contents of `labqcpro/` from this ZIP over the existing staging module directory (preserving database and documents). **Do not deactivate/reactivate** LabQC: 1.1.1 adds one optional table but does not change existing LabQC tables, module ID or permission IDs.
2. Refresh the Dolibarr session and open QC Tests; confirm the new inspection fields and module ID `207603`. The `llx_labqcpro_test_inspection` table is created automatically on first access if absent (requires DB CREATE TABLE privilege).
3. Rerun the six count queries and check counts are unchanged **before generating new data**.
4. Confirm an existing specification, its parameters, the old PASS and FAIL tests, and the associated Audit Trail remain intact.
5. Confirm both historical PDF report types still generate and that no extra blank page is created.
6. Check the Analyst can save an OPEN QC test but cannot finalize; verify a QC Manager can finalize with result fields read-only.
7. With BatchSafe and CAPA enabled, open existing linked records and check links; do not perform an unnecessary new release/rejection of a previously blocked lot.
8. Check desktop and mobile pages, and repeat a save/recalculate operation on an OPEN test.
9. Check the Dolibarr/PHP error logs for warnings/errors during the procedure.

**Pass** only if counts/old records survive, rights work, PDFs and links open, and no new errors occur. Otherwise restore the staging backup and investigate before Stable publication.

## Scope limits

- Multi-company runtime compatibility was intentionally excluded from this 1.1.1 release-validation plan.
- This static check is not an independent 21 CFR Part 11, GMP, or security certification.

## Additional 1.1.1-specific regression tests

- Confirm `SELECT COUNT(*) FROM llx_labqcpro_test_inspection` succeeds after opening QC Tests. Existing six LabQC table counts must remain unchanged before creating new tests.
- Open a QC test created in 1.1.0 without inspection metadata. Confirm no errors, fields are optional and CoA/OOS does not show a blank inspection section.
- Create a new test with 5000 batch parts and 100 inspected, AQL 0.65, third-party reference and inspection type; save a change, reload, inspect field-level Audit Trail.
- Reject invalid input (e.g., 6000 inspected of 5000 total), and confirm changes to a finalized test are blocked on both UI and server.
- Verify Analyst and Manager role separation for new inspection fields and finalization, plus generation permissions for both reports.
- Generate one-page PASS/FAIL PDFs and a long multi-page PDF; verify content and page numbering.
