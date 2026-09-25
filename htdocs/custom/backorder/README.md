# BACKORDER FOR <a href="https://www.dolibarr.org">DOLIBARR ERP CRM</a>

![Backorder banner](img/onbackorder.png) 

## Backorder Module

### Features of backorder:

* Backorders of customers orders in dolibarr is based on open customer orders and shipments made. A partial shipment of your customer order will add backorder in the customer backorder list. You will be able to print a backorder pdf from the order concerned. When the order is classified delivered, the products from the order are removed from the backorder list.
* Backorders of suppliers orders in dolibarr is based on open supplier orders and item receipts made. A partial receive of your supplier order will add backorder in the supplier backorder list. When the order is classified as all received or canceled, the products from the order are removed from the backorder list.

See all backorders in " Commerce / Sales Orders / Backorder List " and/or " Commerce / Purchase Orders / Backorder List " and make a filter with category of product, supplier, ref of product and you will have a total of backorder amount.
Overview of your backorders is also available in sales orders and purchase orders dashboard.


---

## Version

Backorder 1.0 
Need Dolibarr 7.0 mini

---

## Install

### From the ZIP file and GUI interface

- If you get the module in a zip file (like when downloading it from the market place [Dolistore](https://www.dolistore.com)), go into
menu ```Home - Setup - Modules - Deploy external module``` and upload the zip file.


Note: If this screen tell you there is no custom directory, check your setup is correct: 

- In your Dolibarr installation directory, edit the ```htdocs/conf/conf.php``` file and check that following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading ```//```) and assign a sensible value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```
        
---

## Configuration

From your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module and put ON
  - Click on configuration of the module
  - Active or not BackOrder for customers and suppliers
  - Generate BackOrder list

---

## Suppression

Put off the module and delete in custom/htdocs

---

## Please check also our other modules:
- [Mobilid](https://www.dolistore.com/en/modules/407-Connector-for-Mobilid-Mobile-app.html) Dolibarr in your warehouse with barcode reader.
- [Block Outstanding](https://www.dolistore.com/en/modules/1282-Block-Outstanding.html) Block proposals, orders and invoices if outstanding payment amount exceeds limit.

---

## Licenses

### Main code

![GPLv3 logo](img/gplv3.png)

GPLv3 or (at your option) any later version.

See file COPYING for more information.

#### Documentation

All texts and readmes.

![GFDL logo](img/gfdl.png)
