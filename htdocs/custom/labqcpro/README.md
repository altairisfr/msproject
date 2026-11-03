# LabQC Pro for Dolibarr

**Version:** 1.1.1
**Publisher:** PanciucSoft  
**Dolibarr Module ID:** 207603  
**Status:** Stable release package (publication on DoliStore is a separate step)

LabQC Pro adds a laboratory quality-control workflow to Dolibarr for products managed by batch/lot.

## Main workflow

QC Specification → QC Test → Analytical Results → Automatic PASS / FAIL → Finalization & Lock → CoA or OOS Report

## Features

- Product-specific QC specifications
- Analytical parameters and acceptance criteria
- Numeric range and text-target evaluation
- QC tests linked to real Dolibarr product lots
- Automatic PASS / FAIL calculation
- BatchSafe Pro integration:
  - new/open QC test → Under inspection
  - provisional PASS / FAIL while open → remains Under inspection
  - finalized PASS → Released
  - finalized FAIL / OOS → Rejected
- Finalize & lock workflow
- Immutable Audit Trail
- Certificate of Analysis (CoA) for finalized PASS tests
- OOS / QC Test Report for finalized FAIL tests
- Tested by / Approved by / approval date / signature line
- Real lot selection with stock and expiry information

## Important behavior

A finalized QC test becomes read-only. Final CoA/OOS documents are available only after finalization.

If BatchSafe Pro is enabled, LabQC Pro synchronizes QC state with the corresponding real Dolibarr product lot. Existing quality holds and rejections must be reviewed in their own workflows; a later PASS does not necessarily override an existing block. If BatchSafe Pro is disabled, LabQC Pro continues to operate independently.

## Permissions and suggested roles

LabQC Pro separates access into dedicated Dolibarr permissions:

- Read LabQC Pro quality control data
- Create or modify QC specifications and analytical parameters
- Create QC tests
- Enter or modify analytical results
- Finalize and approve QC tests
- Generate Certificate of Analysis or OOS reports
- View the LabQC Pro audit trail

Suggested **Analyst** access: Read + Create QC tests + Enter/modify analytical results.

Suggested **QC Manager** access: Read + Finalize/approve + Generate CoA/OOS + View Audit Trail, plus specification editing when required.

Dolibarr administrators retain full access.

## Requirements

- Dolibarr 18.0 or later
- Products module
- Batch/Lot management for lot-based QC
- PHP 7.4 or later
- BatchSafe Pro is optional, but recommended for automatic lot status control

## Installation

See `INSTALL.md`.

## Upgrade note

For the validated demo environment, module files were deployed through the hosting File Manager because the hosting extraction workflow had previously produced zero-byte PHP files when overwriting read-only files.

## Support

Email: support@panciucsoft.com

## License

GNU GPL v3 or later (`GPL-3.0-or-later`). See `LICENSE.md` and `COPYING`.

## Optional CAPA integration

When CAPA & Deviation Manager 1.1.9+ is enabled, a finalized FAIL / OOS QC test can create or open a linked CAPA deviation with source traceability, product reference and lot/batch prefilled.

## Upgrade compatibility scope

Comparison with 1.1.0 confirms that the six original SQL tables, module ID and all seven permission IDs remain unchanged; 1.1.1 adds a separate optional inspection table. Functional test data persisted across development-build upgrades. A direct 1.0.43-to-1.1.0 upgrade has **not** been exercised against a restored copy of an existing customer database. Back up data and confirm the upgrade on a staging environment before deploying to production. Multi-company support was not validated as part of this release.

## New in 1.1.1

Optional manual fields in the QC Test form: Batch Quantity (parts), Quantity Inspected (parts), AQL Level, Third-Party Reference, and Inspection Type. Field values appear on the detail page and in CoA/OOS PDFs and changes are audited. No automated AQL sampling or acceptance decision is performed. The extra inspection table is created idempotently on the first access to QC Tests or Certificates; back up your database before updating. See DEV1_TEST_PLAN.md.

### Supported interface languages

LabQC Pro is available in English, Romanian, French, German and Spanish. Select the language in your Dolibarr user preferences.
