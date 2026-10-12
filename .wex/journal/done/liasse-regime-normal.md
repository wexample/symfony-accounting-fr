# Liasse fiscale du régime réel normal (2050 à 2059)

Opened: 2026-10-12
Author: agent:main

La liasse du régime simplifié est faite (`FrForm2033DataProvider`). Il manque celle du régime réel normal, formulaires 2050 à 2059, pour les dossiers au-dessus des seuils du simplifié.

## À faire

- Un fournisseur de données par formulaire, sur le modèle de 2033 :
  - 2050-2051 : bilan ;
  - 2052-2053 : compte de résultat ;
  - 2054 à 2059 : immobilisations, amortissements, provisions, créances et dettes, résultat fiscal, plus et moins-values, déficits.
- Le régime du dossier choisit la liasse.
- Les numéros de cases par millésime : voir `drawer-tax-form-field-maps.md` dans ce journal.
- Export au format EDI-TDFC, par un partenaire EDI, ou à saisir à la main.

## Fait (2026-10-12)

- Les formulaires 2050 à 2053 sont des définitions d'état (`liasse_2050` à `liasse_2053`), avec les codes officiels des cases et leurs libellés. `FrLiasseDataProvider` calcule les totaux qui couvrent 2052 et 2053 (HL, HM, HN).
- Testé :
  - le bilan s'équilibre (CO = EE) ;
  - le résultat est le même que DI et que le 2033 ;
  - chaque compte du PCG a sa case, quel que soit le sens de son solde.

Pas fait :
- 2054 à 2059 : mouvements d'immobilisations et d'amortissements, provisions, échéances, résultat fiscal, déficits ;
- l'export EDI-TDFC ;
- la colonne export des ventes : tout est dans la colonne France.
