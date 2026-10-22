# VAT tracking view (simplified regime CA12 deposits), from a MaCompta screenshot

Opened: 2026-10-09
Updated: 2026-10-12
Author: agent:archeology-drawers

## Fait le 2026-10-12 (agent:main)

- Rapport générique dans symfony-accounting : `VatPositionService` (une colonne par période de déclaration, semestres pour le régime annuel ; collectée, déductible dont immobilisations, nette, acomptes payés lus dans les écritures, acomptes attendus, solde), `api/vat-return/position?ledger=&year=`.
- FR : `FrJurisdiction::getVatDepositSchedule()` donne les acomptes de juillet et décembre du régime simplifié (`FrCa12DepositCalculator`).
- Trouvé en route : la TVA déductible sur immobilisations n'était jamais passée sur son compte (44562) ; elle l'est désormais, au prorata des bases, et la ligne 19 de la CA3 la reprend.
- Réponses aux questions : le régime se lit dans le dossier (`vatPeriodicity`), les deux sont gérés ; les acomptes sont des écritures, pas une saisie à part.
- L'écran (tableau par semestre, comme MaCompta) va à l'agent design system avec les autres écrans de la compta (`symfony-accounting/.wex/journal/todo/api-and-interface.md`).

## Read this first — status of this todo

> **This is a proposal for discussion, not an order to code.** It comes from the 2026-10 "bottom of the drawers" archaeology pass. Read it, then discuss it with the owner: every design choice below is to be challenged and validated **before** any code is written. Do not start implementing on your own.
>
> - Context: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/local/network/.wex/knowledge/readme/archeology/index.md.j2`, `sources.md.j2`, and the drawers page linked below.
> - Safety: `NETWORK/local/network` runs on production data — read its code only, never run anything against it. Anonymize anything taken from it. Never copy secrets found in its history.

Drawers page: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/local/network/.wex/knowledge/readme/archeology/drawers-outside.md.j2`

## What it is

A 2021 screenshot of the MaCompta software, kept by the owner as a model: its "Suivi de la TVA" screen. network computes monthly and yearly VAT "tax invoices" (`accounting-invoices.md.j2`) but has no screen that shows the VAT position over the year with deposits.

## Where

`/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/backup/various/Screenshot from 2021-05-10 17-14-03.png` (contains the association's figures: do not publish).

## What it shows

Tabs: Évolution / Résultat / Charges / Suivi TVA / Suivi CA / Échéancier. Under "Suivi TVA", a table with one column per semester and a yearly total; rows: VAT collected on turnover (by rate, including 20 %), deductible VAT on purchases, deductible VAT on fixed assets, net VAT due, **deposits paid (editable)**, balance due; a "Calculer" button. It is the simplified regime (CA12 with two half-year deposits).

## Proposed shape (suggestion)

A read-only "VAT position" report in `symfony-accounting-fr` (simplified regime) built from the entries: per period collected / deductible (goods vs fixed assets) / net / deposits / balance, with deposits as entered data. The other tabs (évolution, result, charges, turnover, schedule) are dashboard ideas for `symfony-charts`.

## Open questions

1. Which VAT regime applies today (monthly CA3 as in network's code, or CA12)?
2. Should deposits be entities (with payment link) or a simple per-period amount?
