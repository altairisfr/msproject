# Altapdf by [Altairis](https://www.altairis.fr) for [Dolibarr ERP/CRM](https://www.dolibarr.org)
*Enhanced PDF templates*

## 2026.06

- ENH: code modernization of Dolibarr values and end PHP warnings
- FIX: height of rental titles
- FIX: comments in productreturn lines

## 2026.03

- NEW: equipements s/n added in description for shipping and invoice PDFs
- NEW: rental insurance print optons per line and total excluding VAT

## 2026.01

- FIX: Dolibarr 23 compatibility in trigger method

## 2025.09.1

- NEW: Hide SIRET and SIREN in delivery address block for the quote, customer order and invoice

## 2025.09

- NEW: Option ALTAPDF\_HIDE\_COMPANY\_NAME\_IN\_SHIPPING\_ADDRESS to hide company name in delivery address block if delivery contact is defined for order

## 2025.08

- NEW: from Dolibarr 21, you now add general conditions to PDFs (in association with core Dolibarr settings)
- ADD: from Dolibarr 22, you can now merge PDFs with customized prefixes from massaction

## 2025.07

- ADD: column discounted unit price
- ADD: activate global const MAIL\_MASS\_ACTION\_ADD\_LAST\_IF\_MAIN\_DOC\_NOT\_FOUND with module
- ADD: delivery site for supplier orders
- FIX: use Dolibarr settings for product reference display
- ADD: allow Dolibarr core electronic signature (see: https://github.com/Dolibarr/dolibarr/pull/33348)
- NEW: show HS code and country if hide product description
- FIX: Non existent function check is module descriptor
- FIX: PHP 8 compatibility

## 2025-01

- better PHP 8 compatibility. Dolibarr 20 compatibility. Orderboard commissions management, prefixing PDF setting

## 2024-09

- PHP 8 and Dolibarr 19 compatibility. banking and commercial contact enhancement.

## 2024.02

- return module is now productreturn: add PDF template and keep retrocompatibility. Fix on rental calculations.

## 2023.12

- fix in return model, new main inclusion approach

## 2023.10

- minor fixes

## 2023.09

- add return PDF template, improved subtotal title management, various improvements and fixes

## 2023.02

- add parameters ALTAPDF_FORCE_HIDEPACK_DETAILS and ALTAPDF_PRINT_UNSHIPPED_LINES

## 2022.10

- add setup for additional blocks on sending sheets

## 2022.08

- add manual_shipping_site on orders, lot of small fixes

## 2022.05

- fix currencybilling module compatibility : conversion was done twice, fix photos

## 2022.04

- fix subtotal compatibility; fix for photos; fix serial numbers on shipping

## 2021.10

- ajout cadre signature supplémentaire

## 2021.09

- infotot size, adaptation v13 (supplier order), fix pagebreak on shipping, display batch infos

## 2021.07

- minor fixes

## 2020.12

- gestion des adresses de livraison

## 2020.10

- subtotal full compatibility

## 2020.8

- add option to hide packs details

## 2020.7

- gestion des packs

## 2020.6

- gestion détail des packs

## 2020.4

- premier changelog; reload object thirdparty after object reload (fix for v12)
