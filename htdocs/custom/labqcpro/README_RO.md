# LabQC Pro pentru Dolibarr

**Versiune:** 1.1.1
**Editor:** PanciucSoft  
**ID modul Dolibarr:** 207603  
**Stare:** Versiune stabilă

LabQC Pro adaugă în Dolibarr un flux de control al calității pentru produse și loturi.

## Flux principal

Specificație QC → Test QC → Rezultate analitice → PASS / FAIL automat → Finalizare și blocare → CoA sau raport OOS

## Funcții

- Specificații QC asociate produselor
- Parametri analitici și criterii de acceptare
- Evaluare numerică și criterii text
- Teste QC asociate loturilor reale Dolibarr
- Calcul automat PASS / FAIL
- Integrare BatchSafe Pro:
  - test QC nou → Under inspection
  - test QC deschis, inclusiv PASS/FAIL provizoriu → Under inspection
  - PASS finalizat → Released
  - FAIL / OOS finalizat → Rejected
- Finalizare și blocare rezultate
- Audit Trail imuabil
- Certificate of Analysis (CoA) pentru teste PASS finalizate
- OOS / QC Test Report pentru teste FAIL finalizate
- Testat de / Aprobat de / data aprobării / linie pentru semnătură
- Selectarea lotului real cu stoc și data expirării

## Comportament important

După finalizare, testul QC devine doar pentru citire. CoA/OOS final poate fi generat numai după finalizare.

BatchSafe Pro este opțional. Dacă este activ, LabQC Pro sincronizează automat starea QC cu lotul real Dolibarr.

## Permisiuni și roluri recomandate

LabQC Pro separă accesul în permisiuni Dolibarr distincte:

- Citirea datelor LabQC Pro
- Crearea sau modificarea specificațiilor QC și a parametrilor analitici
- Crearea testelor QC
- Introducerea sau modificarea rezultatelor analitice
- Finalizarea și aprobarea testelor QC
- Generarea CoA sau a rapoartelor OOS
- Vizualizarea Audit Trail

Acces recomandat pentru **Analyst**: Citire + Creare teste QC + Introducere/modificare rezultate.

Acces recomandat pentru **QC Manager**: Citire + Finalizare/aprobare + Generare CoA/OOS + Audit Trail, plus editarea specificațiilor atunci când este necesar.

Administratorii Dolibarr păstrează acces complet.

## Cerințe

- Dolibarr 18.0 sau mai nou
- Modul Products
- Gestionarea loturilor pentru produsele analizate
- PHP 7.4 sau mai nou
- BatchSafe Pro opțional, recomandat pentru control automat al loturilor

## Instalare

Vezi `INSTALL.md`.

## Suport

Email: support@panciucsoft.com


## Licență

GNU GPL versiunea 3 sau ulterioară (`GPL-3.0-or-later`). Consultați `LICENSE.md` și `COPYING`.

## Limite ale verificării actualizării

Compararea cu 1.1.0 confirmă păstrarea celor șase tabele SQL originale, ID-ului de modul și celor șapte permisiuni; în 1.1.1 se adaugă un tabel separat pentru informațiile de inspecție. Actualizarea directă 1.0.43 → 1.1.0 nu a fost testată pe o copie restaurată a unei baze de date reale. Se recomandă backup și verificare pe o instalare de test înainte de actualizarea producției. Compatibilitatea Multicompany nu a fost validată în această versiune.

## Noutăți 1.1.1

Câmpuri opționale în testul QC: cantitatea lotului, cantitatea inspectată, valoarea AQL introdusă manual, referința terțului și tipul inspecției. Informațiile apar în fișa testului și în rapoartele CoA/OOS, iar modificările sunt înregistrate în Audit Trail. Nu se efectuează calcule AQL automate. La prima accesare a QC Tests se creează automat tabelul adițional de inspecție; baza de date trebuie salvată înainte de actualizare.
