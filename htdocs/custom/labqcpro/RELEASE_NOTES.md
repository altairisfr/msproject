# LabQC Pro 1.1.1 — Stable multilingual release

## New features

- Five optional inspection metadata fields in QC Tests: **Batch Quantity (parts)**, **Quantity Inspected (parts)**, **AQL value (manual)**, **Third-Party Reference**, **Inspection Type**.
- Store metadata per QC test without altering historical QC tables; allow edits to open tests, preserve a field-level Audit Trail and display values read-only after finalization.
- Validate positive integer quantities and reject cases where inspected quantity exceeds batch quantity.
- Display the inspection fields in finalized CoA and OOS PDFs when present; existing tests with no metadata retain their previous report layout.
- Single-page CoA/OOS pagination correction and Unicode-safe page-label rendering, including the Spanish **Página** footer.
- Five user-interface languages: **English, Romanian, French, German and Spanish**, including the new inspection fields, dashboard, audit labels and PDF section text.
- The manually entered AQL is informational; the module does **not** calculate sample sizes or statistical acceptance/rejection under ISO 2859.

## What was verified

- On test.panciucsoft.com (Dolibarr 24.0.0): new QC test creation, metadata saving, field audit, rejecting an invalid inspected quantity, PASS and FAIL workflows, finalization and read-only views.
- Short finalized CoA and OOS PDFs: both fit on one A4 page with the expected inspection details.
- French, German and Spanish dashboard/QC Test interface screenshots visually reviewed.
- CoA PDFs in French, German and Spanish, plus OOS PDF in German, visually reviewed. Spanish footer corrected to **Página 1 / 1**.
- PHP syntax, ZIP structure, module ID and permission IDs, and translation-key consistency verified during stable packaging.

## Upgrade and compatibility

- **Module ID:** 207603 (unchanged); **permission IDs:** 20760301–20760307 (unchanged).
- The six original QC tables are unchanged. A separate optional `llx_labqcpro_test_inspection` table is created on first QC Tests page access if missing (requires database `CREATE TABLE` privilege).
- **No disable/enable cycle required** for updating an existing installation; replace the module files and verify QC Tests.
- Back up the database, documents and module files before production deployment.
- Dolibarr versions V18–V24 and PHP 7.4+ are the declared compatibility range; a full regression across this range has not been performed in this release.
- The following are not yet confirmed by dedicated live regression: restored-customer-database upgrade from 1.0.43, constrained-role permission tests, long multi-page PDFs and multi-company behavior.

## Optional integrations

BatchSafe Pro and CAPA & Deviation Manager remain optional; existing safeguards and integration logic are unchanged.

## Compliance

LabQC Pro is not independently certified for GMP, GxP, FDA 21 CFR Part 11 or other regulated uses. The organization deploying it is responsible for validation and relevant procedures.
