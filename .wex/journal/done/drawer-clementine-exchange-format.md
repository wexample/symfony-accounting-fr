# Clementine (online accountant) exchange format and the 2021 reconciliation round-trip

Opened: 2026-10-09
Updated: 2026-10-09
Author: agent:archeology-drawers

## Read this first — status of this todo

> **This is a proposal for discussion, not an order to code.** It comes from the 2026-10 "bottom of the drawers" archaeology pass. Read it, then discuss it with the owner: every design choice below is to be challenged and validated **before** any code is written. Do not start implementing on your own.
>
> - **Before anything else**: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/PACKAGES/PHP/.wex/local/package-agent-intro.md` — where you stand in the suite, what a package must look like, how to work, and how to judge a drawer todo.
> - Context: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/local/network/.wex/knowledge/readme/archeology/index.md.j2`, `sources.md.j2`, and the drawers page linked below.
> - Safety: `NETWORK/local/network` runs on production data — read its code only, never run anything against it. Anonymize anything taken from it. Never copy secrets found in its history.

Drawers page: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/local/network/.wex/knowledge/readme/archeology/drawers-outside.md.j2`

## What it is

network's books were checked by an online accounting firm (Clementine) for 2018–2021. Outside network, the owner kept the firm's **full data exports** and the folders of the 2021 **reconciliation round-trip** (V1 → V4). The knowledge pages know the HTML ledger parser (`backup/clementine/parsing-v3.js`) and `ClementineDiff`, not these exports. They define a real-world import/export format and the shape of an "accountant diff".

## Where (real books: private, never commit, describe or anonymize)

- `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/COMPTA/CLEMENTINE-EXPORTS/export-clementine-all-{2018,2019,2020,2021}.zip` (2022-11-08). Layout:
  - `tiers.csv`: `Nom, Entreprise, Adresse, Code postal, Ville, Pays, Téléphone, Email, SIRET, Numéro TVA, Solde initial, SIRET, Date import, Est générique ?, Type` (note the duplicated `SIRET` column);
  - `produits.csv`: `Code produit, Nom, Prix, Type, Commentaire, Unité, TVA`;
  - `vehicles.csv`, `utilisateurs/collaborateurs.csv`;
  - `banque/<accountId>/operations.csv`, `banque/caisse/…`, `banque/notes_de_frais/…`, `banque/factures/{achat,vente}/operations.csv` + `documents/` (invoice PDFs), `factures/achat/…`;
  - every `operations.csv` has the header `DateValue,Label, Debit, Credit, Commentaire, Catégorie, Compte, Journal, Fichier` (leading spaces in names);
  - 2020/2021 also `bibliotheque/{ged,pendingPoints,societyLetters,formalisms}` (the firm's review notes, engagement letters).
- `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/COMPTA/CLEMENTINE-2021/`: `V1/general-ledger/<account>.csv` = network's per-account export (`id,accounting_code,date_created,journal,label,debit,credit,invoice_code,entity`) next to the firm's ledger (HTML/PDF), `V1/RESPONSE/` (unpaid invoices at year end, firm's ledger as xls, Stripe "unified payments" CSV), `V2` (changed or forgotten invoices), `V4` (draft annual-accounts booklet), `EXPORT-V2/`, `EXPORT-V3-TEST/<account>/`, `exports-2021-clementine-v{2,3}/{ADD,WRONG_AMOUNT,FROM_418100}` = invoice PDFs sorted by **diff category**.
- Related: `fos:src/Command/Accounting/ClementineDiff.php` (`accounting-bank.md.j2`, `data-sync.md.j2`).

## What to recover

1. The operations CSV as an **accountant export format** (one line per bank/invoice operation with category, account, journal, attached file name) — a simple, accepted way to hand books to a firm, next to the FEC.
2. The diff categories the round-trip produced (`ADD` = missing at the accountant, `WRONG_AMOUNT`, `FROM_418100` = moved out of "clients – invoices to issue"), as the vocabulary of a reconciliation report.
3. The per-account CSV export network already produced in V1 (columns above) — keep it as a feature.

## Proposed shape (suggestion)

- In `symfony-accounting-fr` (French accountant practice): an exporter "operations by journal" in this CSV layout, and optionally an importer of `tiers.csv` / `produits.csv` to seed organizations and products.
- The reconciliation categories go with the lettering / diff engine wherever it lands (`symfony-accounting`).
- Fixtures: build tiny synthetic CSVs with the exact headers; never commit the real ones.

## Open questions

1. Is Clementine still the accountant? If not, is a generic "operations CSV" export still useful?
2. Should network import a firm's export (to compare), or only export to it?

## Classé (2026-10-13)

Abandonné : plus de cabinet Clementine. Le FEC (export, import, validateur) couvre les échanges avec un cabinet ; un rapport de rapprochement ne vaudra que si un cabinet retient à nouveau les livres en parallèle.
