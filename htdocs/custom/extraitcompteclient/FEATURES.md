# Fonctionnalités Extrait Compte Client

Ce document décrit les fonctionnalités du module **Extrait Compte Client** pour Dolibarr.

---

## Génération de documents

### Modèle PDF (`pdf_account_statut`)
- Génération d'un **extrait de compte au format PDF** pour un tiers (client ou fournisseur)
- Affichage des factures avec date, référence, montant TTC et solde restant dû
- Calcul automatique des **totaux** et du **solde global** du compte
- Prise en charge des **avoirs**, **escomptes** et **réductions** dans le détail des paiements
- Personnalisation de la **couleur des lignes** du tableau via l'administration
- Gestion de la hauteur dynamique de l'en-tête en cas de cellules multi-lignes

### Modèle CSV (`doc_account_statut_csv`)
- Génération d'un **extrait de compte au format CSV** avec les mêmes données que le PDF
- Option pour **supprimer les espaces dans les nombres** (séparateur de milliers)
- Séparateur de tags produits configurable

### Génération depuis la fiche tiers
- Bouton **"Générer extrait compte"** ajouté sur la fiche tiers via un hook (`thirdpartycard`)
- **Popup de configuration** permettant de choisir les options avant génération :
  - Période (date de début / date de fin), pré-remplie avec l'exercice fiscal en cours
  - Type d'export (Client, Fournisseur, ou les deux si le tiers est les deux)
  - Modèle de document (PDF, CSV, ou InfraSPlus si le module est activé)
  - Options supplémentaires (cases à cocher)
- Si JavaScript est activé, la génération peut aussi se faire en sélectionnant le modèle de document dans la liste des documents du tiers et en cliquant sur **"Générer"**

---

## Options de génération

### Inclure les factures des filiales
- Permet d'inclure les factures des **sociétés filles** (filiales) du tiers dans l'extrait

### Afficher les factures payées
- Par défaut, seules les factures **non payées** sont affichées
- Cette option permet d'inclure également les factures déjà réglées

### Afficher les dates d'échéance de paiement
- Ajoute une colonne **date limite de règlement** entre le libellé et le montant total

### Afficher les factures abandonnées
- Par défaut, seules les factures validées et classées sont affichées
- Cette option inclut les factures avec un statut **abandonné**

### Afficher le détail des paiements
- Affiche les **détails des paiements** associés à chaque facture (avoirs, escomptes, etc.)
- Disponible en PDF et en CSV

### Afficher les tags/catégories produits
- Ajoute les **catégories des produits** liés aux lignes de facture
- En CSV, les tags sont placés dans une colonne séparée avec un séparateur configurable

### Afficher la référence du tiers
- Affiche la **référence client** sur le document généré

### Support multi-devises
- Option disponible uniquement si le module **Multi-devises** est activé dans Dolibarr
- Affiche les montants dans la **devise de la facture** au lieu de la devise principale

### Référence externe fournisseur
- Pour les extraits fournisseur, affiche la **référence externe** (ref_supplier) au-dessus du libellé

### Champ extra facture
- Possibilité d'afficher un **extrafield de facture** configurable sur l'extrait

---

## Compatibilité

### Module InfraSPack Plus
- Si le module **InfraSPack Plus** est activé, un modèle PDF supplémentaire `InfraSPlus_account_statut` est disponible

### Constante `FACTURE_DEPOSITS_ARE_JUST_PAYMENTS`
- Prise en charge de cette constante Dolibarr : les **acomptes** sont exclus de la liste des factures lorsqu'elle est active

### Multi-entités
- Prise en compte du **multi-entités** pour filtrer les factures par entité
- Option pour déplacer le nom de l'entité au début du nom de fichier généré si le tiers est partagé entre plusieurs entités

### SQL ONLY_FULL_GROUP_BY
- Toutes les requêtes SQL sont compatibles avec le mode **ONLY_FULL_GROUP_BY** (MySQL 5.7.5+)

---

## Permissions

| Code | Description |
|------|-------------|
| `extraitcompteclient->societe->generate_account_statut` | Autorise la génération d'un extrait de compte depuis la fiche tiers |

---

## Configuration

### Options du module (`admin/setup.php`)

| Constante | Description | Type |
|-----------|-------------|------|
| `EXTRAITCOMPTECLIENT_DEFAULT_REF_SUPPLIER` | Afficher la référence externe fournisseur | On/Off |
| `EXTRAITCOMPTECLIENT_ORDERBY` | Ordre de tri des factures sur le document (ASC/DESC) | Sélection |
| `EXTRAITCOMPTECLIENT_DELETESPACEFROMNUMBERONCSV` | Supprimer les espaces dans les nombres sur le CSV | On/Off |
| `EXTRAITCOMPTECLIENT_PRODUCT_TAGS_SEPARATOR` | Séparateur des tags produits dans le CSV | Texte |
| `EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD` | Code de l'extrafield facture à afficher | Sélection |
| `EXTRAITCOMPTECLIENT_COLOR_LINE_PDF` | Couleur des lignes du tableau PDF | Couleur |
| `EXTRAITCOMPTECLIENT_ENTITY_NAME_BEGIN_LOCATION_IN_FILENAME` | Placer le nom de l'entité au début du nom de fichier | On/Off |

### Options par défaut du générateur

| Constante | Description | Type |
|-----------|-------------|------|
| `EXTRAITCOMPTECLIENT_DEFAULT_ADD_SUBSIDIARIES` | Cocher par défaut "Inclure les filiales" | On/Off |
| `EXTRAITCOMPTECLIENT_DEFAULT_INVOICE_PAYED` | Cocher par défaut "Afficher les factures payées" | On/Off |
| `EXTRAITCOMPTECLIENT_DEFAULT_PAYMENT_DEADLINE` | Cocher par défaut "Afficher les dates d'échéance" | On/Off |
| `EXTRAITCOMPTECLIENT_DEFAULT_INVOICE_ABANDONED` | Cocher par défaut "Afficher les factures abandonnées" | On/Off |
| `EXTRAITCOMPTECLIENT_DEFAULT_PAYMENT_DETAILS` | Cocher par défaut "Afficher le détail des paiements" | On/Off |
| `EXTRAITCOMPTECLIENT_DEFAULT_ADD_PRODUCT_TAGS` | Cocher par défaut "Afficher les tags produits" | On/Off |
| `EXTRAITCOMPTECLIENT_THIRDPARTY_REF` | Cocher par défaut "Afficher la référence du tiers" | On/Off |
| `EXTRAITCOMPTECLIENT_DEFAULT_ADD_MULTICURRENCY` | Cocher par défaut "Multi-devises" (si module activé) | On/Off |

---

## Hooks

| Contexte | Méthode | Description |
|----------|---------|-------------|
| `thirdpartycard` | `doActions()` | Intercepte l'action `builddoc` et `confirm_extraitcompteclient_generate_account_statut` pour générer le document |
| `thirdpartycard` | `addMoreActionsButtons()` | Ajoute le bouton "Générer extrait compte" et la popup de configuration sur la fiche tiers |
| `thirdpartycard` | `formObjectOptions()` | Gère la génération depuis la liste des modèles de documents du tiers (JavaScript) |

---

## Traductions

Le module fournit des traductions dans **4 langues** :
- Français (`fr_FR`)
- Anglais (`en_US`)
- Allemand (`de_DE`)
- Italien (`it_IT`)