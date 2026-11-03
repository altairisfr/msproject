## 1.1.1 — Stable multilingual release

- Release all 1.1.1 inspection fields and manual AQL metadata with field-level auditing and immutable finalized records.
- Include inspection context in CoA/OOS PDFs, correct one-page footer pagination, and decode HTML entities in translated page labels.
- Add French, German and Spanish languages alongside English and Romanian, with localized interface and report sections.
- Final UI and PDF validation on Dolibarr 24.0.0 test installation; see RELEASE_NOTES.md for the precise coverage and outstanding compatibility tests.
- Preserve module ID 207603, all seven permission IDs and six existing QC tables; optional additive inspection table created when necessary.

## 1.1.1-dev4 — Spanish PDF footer encoding fix

- Decode any HTML entities in the translated PDF page label before rendering with TCPDF's plain-text `Text()` method.
- Prefer Dolibarr's plain translation accessor when supported; retain a safe fallback for older instances.
- Address the visible Spanish footer issue (`P&amp;aacute;gina` instead of `Página`).
- Preserve document layout, single-page CoA/OOS behavior, all translations, ID 207603, rights, database schema and QC workflows.
- Requires visual re-check in Spanish and a quick CoA/OOS PDF regression on staging.

## 1.1.1-dev3 — Multilingual QA build

- French (fr_FR), German (de_DE) and Spanish (es_ES) translations.
- Translate Dashboard hardcoded strings, PDF report sections and BatchSafe UI notifications.
- No schema/ID/permission/workflow changes.

## 1.1.1-dev2 — PDF pagination correction (development)

- Prevent a nearly empty extra page after short CoA/OOS reports with new inspection metadata.
- Remove the duplicate document-reference table from the end of the body; the test reference remains in the report heading and every page footer.
- Slightly compact report table padding so one-page reports with five inspection fields fit on A4 more reliably.
- Use absolute-positioned footer text instead of Cell() to avoid accidental PDF page creation; keep page numbering for genuine multi-page reports.
- Retain all data, QC calculations, audit events, security checks and optional BatchSafe/CAPA integrations from dev1.
- Requires PDF visual regression on the Dolibarr server (short CoA, short OOS, and a long multi-page case) before release.

## 1.1.1-dev1 — Manual batch inspection metadata (development)

- Add optional Batch Quantity, Quantity Inspected, manually entered AQL, Third-Party Reference and Inspection Type to QC Test creation and open-test editing.
- Preserve existing QC test tables by using a separate entity-aware inspection metadata table, created idempotently on first access; no deactivation/reactivation required.
- Store changes transactionally and log field-level differences to the immutable QC audit history.
- Include inspection metadata in finalized CoA and OOS PDFs when any values are recorded.
- Retain all existing PASS/FAIL, approval locking, BatchSafe/CAPA integration and permissions.
- AQL values are informational only; no sample plan, sample-size or Ac/Re calculation.
- Development version only. DB integration and full functional browser testing remain required before a stable release.

## 1.1.0 — Stable

- Modernized desktop and mobile interface across Dashboard, QC Specifications, Analytical Parameters, QC Tests, Certificates/OOS and Audit Trail.
- Improved professional CoA / OOS PDFs with analytical tables, traceability, decisions and reliable footer pagination.
- Corrected CSRF token handling on PDF generation links.
- Strengthened entity-level authorization on QC record updates, QC specification changes and result recalculation.
- Improved numerical acceptance-limit validation and transactional QC result saving with audit checks.
- Maintained optional BatchSafe Pro and CAPA & Deviation Manager integrations.
- Preserved module ID 207603, permission IDs 20760301–20760307 and the existing six SQL table definitions.
- Static upgrade comparison with 1.0.43 completed. A direct upgrade on a restored 1.0.43 database has not been tested; see UPGRADE_VALIDATION.md.
- Multi-company runtime integration was not tested for this release.

## 1.1.0 — Release Candidate / compatibility audit
- Consolidated the dev20 security, responsive UI, audit, PDF and CAPA/BatchSafe updates.
- Checked that SQL definitions and all seven permission IDs match module version 1.0.43.
- Updated visible release-channel labels and English/Romanian documentation.
- No module ID change (207603); no SQL migration included.
- Pending: real direct upgrade from 1.0.43 on a restored database copy, and installation-specific regression tests.

## 1.1.0-dev20 — Integrity and security hardening
- Require POST for data-changing LabQC actions; keep Dolibarr's native CSRF token validation.
- Recalculate test status only within its entity and fail the transaction if reading or updating status fails.
- Roll back QC test creation if its creation audit event cannot be persisted or commit fails.
- Reject nonnumeric analytical limits and inverted numeric ranges when adding a specification parameter.
- Correct README version and historical development changelog headings.
- Keep ID 207603, seven rights, existing DB schema, PDF formatting and optional CAPA/BatchSafe integrations unchanged.

## 1.1.0-dev19
- Corrected narrow-screen Result field layout: numeric value input stays alongside a non-wrapping unit (e.g., pH).
- Kept v1.1.0-dev18 entity protection, module ID 207603 and all seven permissions unchanged.
- No database migration or analytical logic changes.

## 1.1.0-dev18
- Security: refuse result updates unless the parent QC test belongs to the active Dolibarr entity.
- Fail closed when the entity-scoped test status cannot be loaded.
- Preserved module ID 207603, rights IDs 20760301–20760307, DB schema, CAPA/BatchSafe integrations and dev17 UI.

## 1.1.0-dev17
- Restored the shared modern page hero on the normal QC Tests page (previously only shown for the missing-table error branch).
- Kept the already validated mobile QC Specifications and QC Tests cards unchanged.
- Reduced excess vertical sizing of Generate CoA/OOS PDF actions in narrow mobile cards.
- No modifications to QC workflows, permission IDs, module ID 207603 or database schema.

## 1.1.0-dev16
- Rebuilt all QC Specification forms with structural responsive field groups to remove label and input overlaps on phones.
- Fixed wrap/overlap in QC Tests mobile list through dedicated field value containers.
- Corrected HTML entity display in secondary-page hero titles and improved CoA/OOS button sizing on mobile.
- Preserved Dashboard, Audit Trail, QC workflows, database schema, module ID 207603 and permission IDs.

## 1.1.0-dev15
- Reworked the remaining LabQC secondary pages to visually match the modern dashboard: shared page headings, consistent form spacing, cards, typography and controls.
- Changed QC Specification and Analytical Parameter tables to mobile cards with readable labels, no word-by-word narrow columns.
- Modernized QC Tests listing for mobile; applied consistent visual presentation to Certificates/OOS and Audit Trail.
- Presentation only: preserved all POST actions, fields, CSRF tokens, QC processing, integrations, SQL schema, module ID 207603 and permission IDs.

## 1.1.0-dev14
- Restrict QC specification product associations to products visible in the active Dolibarr entity (or shared entity 0).
- Preserve entity-scoped updates and analytical parameter controls.
- No SQL schema, module ID, or permission changes.

## 1.1.0-dev13
- Fixed specification list column widths and non-wrapping Version / Status headings.
- Cache-busted the specifications stylesheet to apply the update on refresh.
- No change to workflows, data schema, ID 207603 or permissions.

## 1.1.0-dev12
- Fixed QC Specifications labels and list headers using markup-specific CSS selectors.
- No changes to database, permissions or QC workflows.

## 1.1.0-dev11
- Compact QC Specifications form; prevent Description label from wrapping.
- Correct Version and Status heading widths and responsive table layout.
- Presentation-only change; no SQL, permissions, ID, or workflow changes.

## 1.1.0-dev10
- Security: verify active entity ownership of QC specifications before adding or deleting analytical parameter lines.
- Preserve module ID 207603, permission IDs, database schema and existing analytical calculations.

## 1.1.0-dev9
- Fixed spurious nearly empty second page on CoA and OOS reports by drawing footers on existing PDF pages with automatic page breaks disabled.
- Retained module ID 207603, permissions, calculation logic and database schema.

## 1.1.0-dev8
- Redesigned CoA and OOS PDF layouts with improved traceability and decision separation.
- No changes to test calculations, finalization or module identifiers.

## 1.1.0-dev7
- Fixed CSRF token on CoA/OOS PDF generation links using Dolibarr newToken().
- Preserved CSRF protection, module ID 207603, permissions and database schema.

## 1.1.0-dev6
- Audit Trail: improved table readability, nowrap usernames and references on desktop.
- Tablet/mobile: audit event cards without cramped table columns.
- Presentation only; audit data, SQL, module ID 207603 and permissions unchanged.

## 1.1.0-dev6
- Refined QC Test detail summary and analytical results layout.
- Clarified BatchSafe status is lot-level, not necessarily the current test decision.
- Refreshed Certificates / OOS document listing.
- No DB migration; module ID 207603 and permissions preserved.

## 1.1.0-dev4
- Modernized QC Tests layout, forms, results and tables without changing data processing.
- Maintained module ID 207603 and original permission IDs.
- No schema changes.

## 1.1.0-dev4
- Refreshed QC Specifications forms and listing layout to match the new dashboard.
- Scoped responsive styling; no SQL/schema, module ID, permissions or workflow changes.
- Retained LabQC module ID 207603.

## 1.1.0-dev2
- Rebuilt dashboard with entity-scoped live QC counters and linked workflow.
- Corrected development release badge and clarified optional integration status.
- Retained module ID 207603 and existing permission IDs; no database migration.

# Changelog

## 1.1.0-dev1 — First development build (UI and ID audit)

- Verified module ID 207603 and unchanged rights IDs 20760301–20760307.
- Added a common CSS layer for improved contrast, table spacing, focus visibility and responsive layouts.
- Preserved existing QC workflows, CAPA integration and database schema.
- Customer enhancement requests pending verification from support messages.


## 1.0.43 — CAPA scalar prefill transport

- CAPA integration now sends scalar source fields only.
- Removed multiline `description` and `description_b64` transport from CAPA create links.
- Added scalar product/specification fields so CAPA & Deviation Manager can rebuild a clean multiline description server-side.
- No LabQC database schema or QC workflow change.

## 1.0.42 — CAPA multiline prefill fix

- CAPA prefill descriptions were additionally transmitted as URL-safe base64 so real line breaks could survive the URL/request sanitization path.
- The original plain-text `description` parameter was kept for backward compatibility with older CAPA versions.
- Intended for CAPA & Deviation Manager 1.1.9+; no LabQC database schema or QC workflow change.

# LabQC Pro 1.0.41 — CAPA Integration

- Added optional integration with CAPA & Deviation Manager for finalized FAIL / OOS QC tests.
- Finalized FAIL / OOS tests can open a prefilled CAPA deviation with QC source, product reference, lot/batch, Major severity and Quality Control department.
- Existing CAPA deviations linked to the same LabQC test are detected and opened instead of offering another create action.
- Integration is permission-aware and remains hidden when CAPA & Deviation Manager is disabled.
- No LabQC database schema change.

# LabQC Pro 1.0.40 — DoliStore Release Candidate

- Prepared the validated Stable branch for DoliStore packaging rules.
- Package license changed to GNU GPL v3 or later (`GPL-3.0-or-later`) for Dolibarr/DoliStore compatibility; full GPL text is included in `COPYING`.
- Added standardized GPL headers to PHP source files.
- Replaced page bootstrap loading with the Dolibarr-recommended `main.inc.php` discovery pattern so the module can run from both the standard `custom/labqcpro` location and a root-level `labqcpro` directory.
- Added direct support contact `support@panciucsoft.com`.
- No database schema change and no QC workflow change from 1.0.39.

# LabQC Pro 1.0.39 — Stable

- Promoted the validated 1.0.x development line to Stable.
- Dashboard status now displays Stable / Stabil through the native EN/RO language files.
- Synchronized descriptor and VERSION to 1.0.39.
- Updated README, Romanian README, installation guidance and validation plan to reflect the finalized approval-gated BatchSafe workflow.
- Final validated workflow: open QC test → Under inspection; finalized PASS → Released; finalized FAIL/OOS → Rejected.
- Finalized EN/RO responsive UI, granular Analyst/QC Manager permissions, immutable Audit Trail, CoA and OOS reports.
- No database schema change from 1.0.38.

# LabQC Pro 1.0.38

- Audit Trail date/time now uses the European `dd.mm.yyyy HH:MM` format in the active Dolibarr user timezone.
- Fixed double-encoded translated audit values, so Romanian verdict text such as `În așteptare` is displayed normally instead of HTML entities.
- Raw immutable audit records remain unchanged in the database; the fix only affects display formatting.
- Desktop and mobile Audit Trail layouts are otherwise unchanged.
- No database schema change.

# LabQC Pro 1.0.37

- Certificates / OOS list now displays QC test dates in the same European `dd.mm.yyyy` format as the generated PDFs.
- The OOS / QC Test Report title uses a slightly smaller PDF title size so the longer Romanian title remains on one line.
- CoA title size and desktop/mobile document list layout are otherwise unchanged.
- No database schema change.

# LabQC Pro 1.0.36

- Finalization date shown on the QC test card now uses the European `dd.mm.yyyy HH:MM` format in the user's Dolibarr timezone.
- New BatchSafe synchronization reasons are localized through LabQC language files.
- Romanian BatchSafe reasons now use `LabQC finalizat ...` / `Test LabQC în desfășurare ...` for new events.
- BatchSafe automatic synchronization note is localized for new events.
- Existing BatchSafe history is intentionally left unchanged to preserve the audit trail.

# LabQC Pro 1.0.35

- Fixed Romanian diacritics in dynamically updated QC Test select placeholders and lot helper text.
- JavaScript UI strings are now decoded before JSON serialization, preventing HTML entities such as `&icirc;` and `&acirc;` from being displayed literally.
- No database schema change.

## 1.0.34 — 2026-08-18
- Added native Dolibarr language files for English (`en_US`) and Romanian (`ro_RO`).
- Localized module menus, permissions, dashboard, QC specifications, QC tests, Certificates/OOS, Audit Trail and generated CoA/OOS PDFs.
- Kept PASS/FAIL terminology unchanged for laboratory clarity.
- No database schema change.

## 1.0.32 — 2026-08-18
- CoA and OOS final PDFs now use a fixed European date format: `DD.MM.YYYY`.
- Date/time values in final PDFs now use 24-hour time: `DD.MM.YYYY HH:MM`.
- Test date, approval date and PDF generation timestamp use the same format while preserving Dolibarr user timezone conversion.
- No database schema changes.

## 1.0.31 — 2026-08-18
- CoA/OOS PDFs now identify the laboratory user who entered or most recently modified analytical results as **Tested by**, instead of the user who only generated the PDF.
- PDF generation is labeled **Generated** and uses the active Dolibarr user locale/timezone.
- Test date and approval date now use Dolibarr date formatting for consistent locale-aware presentation.
- Legacy tests without a `result_updated` audit event fall back to the QC test creator for the Tested by value.
- No database schema changes.

## 1.0.30 — 2026-08-18
- CoA and OOS PDF summaries now include an explicit Overall result: `Complies with specification` for PASS and `Out of specification (OOS)` for FAIL.
- Final decision is now expressed as `Approved` for PASS and `Rejected` for FAIL instead of repeating the raw PASS/FAIL status.
- Position placeholders are normalized in every final PDF, including historical approvals that stored `—`, `–` or `-`; missing positions now consistently render a dedicated writing line.
- No database schema changes.

## 1.0.29 — 2026-08-18
- CoA/OOS approval block: when the approver position is empty, the placeholder em dash was replaced by a dedicated writing line below the Position label for cleaner PDF layout.

## 1.0.28 — 2026-08-18

- Improved mobile Certificates / OOS card layout.
- Product text now expands the card row correctly instead of overlapping Batch / Lot.
- Added automatic height and consistent line-height for multi-line values.
- Added extra spacing for Product and Certificate rows on narrow screens.

## 1.0.27 — 2026-08-18
- Reworked Certificates / OOS list for mobile screens.
- Each QC test is displayed as a vertical card on phones with QC Test, Product, Batch / Lot, Test date, Status and Certificate labels.
- Document actions now wrap cleanly and remain inside the card.
- Desktop table layout is unchanged.

## 1.0.26 — 2026-08-18

- Refined Audit Trail Action badges with clearly differentiated colors on desktop and mobile.
- Created uses blue, Result updated uses purple, Status changed uses amber, and Finalized uses green.
- Technical action codes remain immutable in the database and continue to be available in the badge tooltip.
- No database schema changes.

## 1.0.25 — 2026-08-18

- Improved Audit Trail Action presentation on desktop and mobile.
- Replaced technical codes such as `test_finalized`, `status_changed`, `result_updated` and `test_created` with readable labels.
- Added Dolibarr-native status badges for Created, Result updated, Status changed and Finalized while preserving the immutable technical action codes in the database.
- Added a tooltip that exposes the original technical action code when needed for troubleshooting.

## 1.0.24 — 2026-08-18

- Mobile Audit Trail: Previous and New now use the same font size and typography as the other fields.
- Removed the Dolibarr `small` class from Previous/New audit values.
- Desktop layout unchanged.

## 1.0.23 — 2026-08-18

- Fixed the final wrapped line in mobile Audit Trail cards extending below the card border.
- Replaced the mobile audit grid-cell layout with a flex layout so multi-line Note values expand the card height correctly.
- Added extra bottom spacing to the Note row and preserved the desktop audit table layout.

## 1.0.22 — 2026-08-18

- Fixed long Audit Trail values overflowing mobile cards, especially the Note field.
- Added dedicated mobile value wrappers with safe word wrapping for Note, Previous, New, QC Test, User and other audit fields.
- Preserved the desktop Audit Trail table layout.

## 1.0.21 — 2026-08-18

- Improved Audit Trail mobile layout: each audit event is rendered as a readable card with Date, QC Test, Action, Object, User, Previous, New and Note fields stacked vertically.
- Removed horizontal/cramped audit columns on narrow screens while preserving the desktop table layout.
- Dashboard version label now reads the installed VERSION file instead of using a hardcoded old version number.

## 1.0.20 — 2026-08-18

- Corrected BatchSafe Pro release gating: provisional PASS/FAIL results no longer change the lot to a final operational status.
- While a QC test remains OPEN, the linked lot stays Under inspection even after analytical results calculate PASS or FAIL.
- Finalized PASS now releases the BatchSafe lot.
- Finalized FAIL/OOS now moves the BatchSafe lot to Rejected.
- This prevents an Analyst from indirectly releasing a lot simply by saving passing laboratory results.


## 1.0.19 — 2026-08-18

- Fixed mobile QC result cards where the `(mandatory)` text from Parameter could overlap the Method label.
- Wrapped each result-cell value in a dedicated mobile content container so labels and values occupy stable grid columns.
- Kept Result and Comment fields responsive without changing the desktop six-column table.


## 1.0.18 — 2026-08-18

- Reduced analytical result and comment field widths on desktop.
- Reworked the QC result table into stacked mobile cards on narrow screens so all fields fit inside the viewport.
- Added mobile labels for Parameter, Method, Specification, Result, Verdict and Comment.
- Preserved the standard six-column QC result table on desktop.


## 1.0.17 — 2026-08-18

- Rebuilt the Create QC Test form with a responsive card/grid layout instead of converting table rows to block elements on mobile.
- Fixed Batch/Lot help text overlapping the Test date label.
- Fixed the Create QC Test button overlapping the Notes field on narrow screens.
- Preserved full-width Dolibarr Select2 controls and the desktop two-column field layout.


## 1.0.16 — 2026-08-18

- Fixed overlapping labels and controls in Create QC Test on mobile.
- Added responsive handling for Dolibarr Select2 wrappers, including full-width and non-floating controls.
- Added reliable vertical spacing and row clearing for mobile form fields.
- Kept the desktop form layout unchanged.


## 1.0.15 — 2026-08-18

- Fixed QC Tests mobile layout so form fields stay inside the viewport.
- On narrow screens, Create QC Test labels and controls stack vertically.
- Removed fixed minimum-width behavior from Product, Specification, Batch/Lot and reference fields on mobile.
- QC test creation and test list columns now expand to full width on phones.
- Wrapped the QC test list in Dolibarr's responsive table container.


## 1.0.14 — 2026-08-18

- Fixed the LabQC top and left menu permission expressions for Dolibarr 23.
- Administrator access now uses a menu-safe `$user->admin` expression instead of `!empty($user->admin)`.
- Keeps server-side permission checks unchanged, including administrator bypass and granular user rights.

# Changelog

## 1.0.13 — 2026-08-18
- Added production permissions and role separation.
- Added separate rights for read, specification editing, QC test creation, analytical result entry, final approval, CoA/OOS generation, and audit viewing.
- Added server-side authorization checks for all protected LabQC Pro actions.
- Added read-only UI behavior when a user can view QC data but cannot modify it.
- Dolibarr administrators retain full LabQC Pro access.

## 1.0.12 — 2026-08-12
- Improved Audit Trail display.
- Technical JSON snapshots remain stored for traceability.
- Audit UI now shows readable Result / Verdict / Comment values.
- Analytical parameter names are shown in audit rows.

## 1.0.11 — 2026-08-12
- Added QC test finalization and result locking.
- Added approval record with approver and approval date.
- Added immutable Audit Trail.
- CoA/OOS generation requires finalized QC test.
- CoA/OOS approval block uses stored approval information.

## 1.0.10 — 2026-08-12
- Batch/Lot field now selects only real Dolibarr product lots.
- Added server-side validation against non-existent lots.
- Product selection filters QC specifications and lots.
- Lot list shows stock and expiry information.

## 1.0.9 — 2026-08-12
- Added BatchSafe Pro integration.
- New QC test sets lot to Under inspection.
- PASS releases the lot.
- FAIL/OOS quarantines the lot.
- Preserves manually blocked/rejected/recalled states where applicable.

## 1.0.7–1.0.8
- Added separate CoA and OOS/QC Test Report behavior.
- Stabilized complete module package and menu navigation.

## 1.0.4–1.0.6
- Added PDF Certificate of Analysis.
- Added aligned analytical result table.
- Added Prepared by / Approved by / approval date / signature block.

## 1.0.3
- Added QC Tests and automatic PASS / FAIL evaluation.

## 1.0.2
- Added QC Specifications and analytical parameters.

## 1.0.0
- Initial LabQC Pro development module.
