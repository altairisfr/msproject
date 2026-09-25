# CHANGELOG BACKORDER FOR <a href="https://www.dolibarr.org">DOLIBARR ERP CRM</a>

## 1.0.0
- Initial version

## 1.0.1
- Dolistore version

## 1.0.2
- Fix amounts alignment in backorder lists
- Fix supplier filter

## 1.0.3
- Fix object delete should get possible backorders again

## 1.0.4
- Fix remove from supplier backorders when received completely.
- Improve translations and add Spanish translations.

## 1.0.5
- Fix backorder amount alignment in order list

## 1.0.6
- Fix Multicompany compatibility

## 1.0.7
- Also add orders with no shipment.
- Also add orders with no receiving's.
- Add new field in list, qty in backorder.
- Fix add trigger for Dolibarr 13 reception.
- Fix delete backorder on delete order.

## 1.0.8
- Fix for Dolibarr 13, show label in supplier backorder list

## 1.0.9
- Fix 1.0.7 regression partial supplier order receive.
- Fix delete shipment and delete reception did not set correct backorder qty and amount.

## 1.0.10
- Fix regression on 1.0.7 for multicompany.
- Backorderlist not filtered correctly on entity.

## 1.0.11
- Add customer filter in customer backorder list.
- Fix store search value in input field.
- Fix ref_client size
- Add support for Dolibarr 14 edit/delete receipt
- Fix exclude receipts from draft reception if reception module used.
- Tested on Dolibarr 14

## 1.0.12
- Fix filters lost on page change.
- Tested on PHP 8.0

## 1.0.13
- Add options to also show unshipped services in customer and supplier backorder list.
- Add comments in sql files for using backorder tables in custom sql queries.
- Improve error handling.
- Fix overwrite module settings with default values on deactivate - activate.
- Fix messed up backorder PDF for more the one page docs.
- Fix translation conflict for column 'Backorder line qty'.

## 1.0.14
- Remove useless code and improve performance
- Add cron methods to regenerate backorders in background. Usefull when using some import tool for orders, shipments or receivings which does not run standard object triggers.
- Add status if shippable or not in list.
- Show price base in amount title.
- Tested on Dolibarr 15

## 1.0.15
- Fix for cancelled orders

## 1.0.16
- Dolibarr 16 compatibility
- New status 'Partly shippable' to show if one or more of the lines containing a certain product is fully shippable but not all of the lines.
- Show multicompany environnement in backorderlist

## 1.0.17
- Dolibarr 17 compatibility
- New status 'Not fully shippable' to show there is some stock available but not for full shipment.
- Fix translation conflicts.
- Add separate right for supplier backorder.

## 1.0.18
- Dolibarr 18 compatibility.
- Tested on PHP 8.1.
- Lost tags/vendor filter on sorting.

## 1.0.19
- Dolibarr 19 compatibility.
- Add Italian translation thanks to https://19.coop/
- Add filter on backorder status thanks to https://www.altairis.fr/
- Add project column with sort and filter, reload and regenerate required

## 1.0.20
- Dolibarr 20 compatibility.
- Add column with user create order.
- Fix missing trigger on supplier order re-open from marked received.
- Fix missing trigger link order project
- Fix delete reception line.
- Update fetchAll to universal search format for V20

## 1.0.21
- Dolibarr 21 compatibility.
- Fix missing project search for supplier backorder

## 1.0.22
- Dolibarr 22 compatibility.
- Do not show duplicate backorder list rows when line details are not visible. 
- When grouped (no line details), the status will be partly or not fully if one of the products is not shippable.
- When a line related field is visible, product column will always be visible.
- Allow different column visibility for supplier backorder list.

## 1.0.23
- Dolibarr 23 compatibility, min version new V13.
- Fix do not use order list check left column if enabled.
- Add shippable status in order card (Min V22 required).
- Disable generate backorder button if no backorder.
- Add warning in supplier order card line if line in backorder (Min V22 required).
- Fix dolibarr V20+ regression on genererate supplier backorder when BACKORDER_SUPPLIER_ORDER_NONRECEIVE not set.
