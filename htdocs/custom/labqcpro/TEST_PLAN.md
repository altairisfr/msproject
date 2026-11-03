# LabQC Pro 1.1.1 — Validation Test Plan

## Validated scenarios on the demo workflow

### QC specification
- Product-specific specification created.
- Numeric acceptance criterion tested.
- Text-target criterion tested.

### Real lot selection
- Product selection filters specification.
- Product selection loads real Dolibarr lots.
- Lot label displays stock and expiry.
- Non-existent free-text lot is no longer accepted.

### PASS flow
- New QC Test → IN PROGRESS.
- BatchSafe → Under inspection.
- Compliant results → PASS.
- While OPEN, BatchSafe remains Under inspection.
- Finalize & Lock → results read-only.
- Finalized PASS → BatchSafe Released.
- Audit includes test_created, result_updated, status_changed, test_finalized.
- Finalized PASS test → Generate CoA PDF.

### FAIL / OOS flow
- Non-compliant result → FAIL.
- While OPEN, BatchSafe remains Under inspection.
- Finalize & Lock → results read-only.
- Finalized FAIL/OOS → BatchSafe Rejected.
- Finalized FAIL test → Generate OOS Report PDF.

### Audit
- Immutable event records displayed.
- Raw technical snapshots remain in database.
- UI renders readable result and verdict values.

### Permissions / roles
- Read-only user can open LabQC pages but cannot create or modify records.
- Analyst can create QC tests and enter analytical results when granted the corresponding rights.
- Analyst without approval rights cannot finalize a QC test.
- QC Manager can finalize/approve completed tests.
- CoA/OOS generation is blocked without the document generation right.
- Audit Trail is blocked without the dedicated audit right.
- Dolibarr administrator retains full access.

## Publication validation status

- Dolibarr numeric module ID assigned: `207603`.
- English and Romanian UI regression: validated on desktop/mobile workflow.
- Dedicated Lab Analyst and QC Manager permissions: validated.
- PASS release gate and FAIL/OOS rejection gate with BatchSafe Pro: validated.
- CoA and OOS PDF generation: validated.
- Audit Trail desktop/mobile rendering: validated.
- Upgrade path through the development series: validated in the demo environment.
- Clean-install execution on a separate fresh Dolibarr instance remains recommended before marketplace publication.

## BatchSafe release-gate regression (1.0.20)

1. Sign in as an Analyst with result-entry rights but without finalization rights.
2. Create/open a QC test linked to a BatchSafe-managed lot; verify BatchSafe = Under inspection.
3. Enter passing mandatory results and save; verify LabQC status = PASS, Finalization = OPEN, BatchSafe remains Under inspection.
4. Sign in as a QC Manager and finalize the PASS test; verify BatchSafe = Released.
5. Repeat with failing mandatory results; before finalization BatchSafe remains Under inspection, and after finalization BatchSafe = Rejected.

## Clean-install validation completed

Validated on a fresh Dolibarr 23.0.3 installation with Products, Stock and Lot/Serial management enabled:
- external module deployment and activation
- new product, warehouse and real lot stock
- new QC specification and analytical parameters
- PASS QC test creation, result evaluation, finalization and locking
- immutable Audit Trail from empty database
- CoA generation
- operation with BatchSafe Pro disabled

The Analyst / QC Manager separation, PASS/FAIL flows and optional BatchSafe Pro integration were also validated on the integration environment.

## 1.1.1 inspection metadata validation

User-verified on test.panciucsoft.com (Dolibarr 24.0.0, mobile): QC test creation with five metadata fields, editing/reload, field-level audit, rejecting inspected quantity > lot quantity, PASS and FAIL finalization/read-only display, and one-page CoA and OOS containing the metadata.

Still to check before production deployment: older records lacking metadata, a test with many analytical rows/long notes, two restricted-role accounts, and error logs. See UPGRADE_VALIDATION.md.
