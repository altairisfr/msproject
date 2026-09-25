# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Extrait Compte Client** is a Dolibarr custom module (ID: 163030) by Opendsi/Easya Solutions that generates customer and supplier account statements as PDF or CSV documents. It hooks into the third-party card (`thirdpartycard` context) to allow document generation directly from the Dolibarr societe page.

- **Compatibility**: Dolibarr 14–22, PHP 7.0–8.4 (defined in `.opendsi_info.json`)
- **Version**: stored in `VERSION` file, read by the module descriptor at runtime
- **Language**: French is the primary language; translations exist in `langs/{fr_FR,en_US,de_DE,it_IT}/`

## Architecture

### Key Components

- **Module descriptor** (`core/modules/modExtraitCompteClient.class.php`): Registers the module, hooks (`thirdpartycard`), models, permissions, and constants. Extends `DolibarrModules`.
- **Hook actions** (`class/actions_extraitcompteclient.class.php`): `ActionsExtraitCompteClient` — intercepts `builddoc` action on the third-party card to generate account statement documents. This is the main entry point for document generation triggered via the UI.
- **Business logic** (`class/extraitcompteclient.class.php`): `ExtraitCompteClient` — `getData()` method runs SQL queries to fetch invoice/payment data for customer or supplier account statements. Handles multicurrency, subsidiaries, payment details, product tags, and various filtering options.
- **Document generators** (`core/modules/societe/doc/`):
  - `pdf_account_statut.modules.php` — PDF output model
  - `doc_account_statut_csv.modules.php` — CSV output model
- **Admin pages** (`admin/`): `setup.php` (module configuration), `about.php`, `changelog.php`
- **Lib** (`lib/extraitcompteclient.lib.php`): Admin tab preparation helper

### Configuration Constants

The module uses many `EXTRAITCOMPTECLIENT_*` constants for options (payment details, multicurrency, product tags, sort order, abandoned invoices, etc.). These are managed via the admin setup page.

## Branching

- `2022.5` is the main/stable branch
- `2026_rc` is the current release candidate branch
- Feature branches follow the pattern `2026_rc_*`

## CI

GitLab CI is configured via `.gitlab-ci.yml`, which includes a shared pipeline template from `opendsi/ci-templates`.

## Translation Files

Two lang files per locale:
- `extraitcompteclient.lang` — module-specific strings
- `opendsi.lang` — shared Opendsi/Easya branding strings

Load with: `$langs->load('extraitcompteclient@extraitcompteclient')` and `$langs->load('opendsi@extraitcompteclient')`

## SQL

SQL migration files are in `sql/` (e.g., `update.7.0.12.sql`). The module descriptor loads them via `$this->_load_tables('/extraitcompteclient/sql/')`.

## Changelog

`ChangeLog.md` follows the Keep a Changelog format (French). Update it with every version bump.