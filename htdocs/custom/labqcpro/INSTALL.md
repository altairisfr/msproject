# LabQC Pro — Installation / Upgrade

## New installation

1. Back up the Dolibarr database and `custom/` directory.
2. Extract the package so the module is located at:
   `htdocs/custom/labqcpro/`
   (or the equivalent `custom/labqcpro/` directory in your hosting layout).
3. Confirm PHP files are not zero bytes and use normal readable permissions such as `0644`.
4. In Dolibarr, open **Setup → Modules/Applications**.
5. Enable **LabQC Pro**.
6. For an existing installation, take verified database and module backups and test the update on a copy. This release adds an optional inspection metadata table, created automatically on the first QC Tests page load by a database user with CREATE TABLE privileges. Existing LabQC tables and permission identifiers are unchanged; a disable/enable cycle is not required.
7. Open **LabQC → Dashboard** and follow `UPGRADE_VALIDATION.md`.

## Required functional test

Create a QC specification with at least:
- one numeric parameter (for example Assay 98–102 %)
- one text parameter (for example Appearance = White tablets)

Then:
1. Create a QC Test for a real Dolibarr lot.
2. Confirm BatchSafe status becomes **Under inspection** when BatchSafe Pro is enabled.
3. Enter compliant results and confirm QC status becomes **PASS** while BatchSafe remains **Under inspection** until approval.
4. Finalize the PASS test with a user holding approval rights; confirm results become read-only and BatchSafe becomes **Released**.
5. Create or use another test with a non-compliant result and confirm **FAIL / OOS** while BatchSafe remains **Under inspection** until approval.
6. Finalize the FAIL/OOS test; confirm results become read-only and BatchSafe becomes **Rejected**.
7. Check Audit Trail.
8. Generate CoA for finalized PASS and OOS Report for finalized FAIL.

## Release channel

Version 1.1.1 is packaged as **Stable** after functional checks of both short CoA and OOS reports and the inspection metadata workflow on test.panciucsoft.com. A direct upgrade from 1.0.43 against a restored existing database has **not** been executed. Back up the database, documents and module files, then verify the upgrade on a copy before production deployment. Multi-company runtime compatibility was not tested.

## Upgrade safety

Do not delete LabQC Pro database tables during upgrades.

If your hosting control panel creates zero-byte PHP files when overwriting read-only files:
- set existing module PHP files to `0644`
- deploy through File Manager
- verify file sizes after extraction
