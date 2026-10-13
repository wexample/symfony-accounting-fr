# Golden reference outputs for FEC, balance, ledger and liasse tests (outside network)

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

Several folders outside network hold **complete outputs produced by other tools on the same books**, plus the blank official forms. They are the best expected values for tests of the closing chain (FEC, balance, general ledger, VAT, liasse 2033/2035/2065/2572), which `accounting-reports.md.j2` says was validated only "within rounding" against the accountant.

## Where (real books: private fixtures only)

- `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/backup/various/MACOMPTA-TEST-2018/` and `MACOMPTA-TEST-2019/`: from the MaCompta software: FEC, balance, general ledger, journals, CA3, detailed balance sheet and income statement (normal regime), control report, 2035 liasse pages as PNG. (The page only mentions the `macompta/` folder.)
- `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/COMPTA/CLEMENTINE-2020/FINAL/`, `COMPTA/CLEMENTINE-2021/FINAL/`: the firm's final data, and for 2021 the **final liasse fiscale PDF** and the **annual accounts booklet (plaquette) PDF**. Also `NETWORK/backup/various/Plaquette comptable WEXAM 2021 PROJET V2.pdf`, `Grands livres … 2021.pdf`.
- Blank forms: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/sources/LIASSES/2018/*.pdf` (2033 A–G, 2065, 2572) and `LIASSES/2019/2572-sd_2420.pdf` (2021-02-23): the CERFA templates behind network's liasse PDFs (`src/Pdf/Accounting2018/**`).
- Mapping references: `NETWORK/backup/various/demo-bilan-comptable.png`, `demo-compte-resultats.png` (public table mapping balance-sheet / P&L lines to PCG account ranges), `Comptes annuels complet.xls`, `ca12.pdf.pdf` (a filled CA12), `pcg-plan-comptable-general.pdf`.

## What to recover

- For each year 2018–2021: expected totals (balance per account, result, VAT due, liasse line values) as a small YAML/JSON table, extracted by hand from these documents, to assert against the new engine.
- The line → account-range mapping of the demo images, to check `AccountingCategoryRelation` (1799 rows in prod, seeded from `-balance.json` / `-income.json`).
- The blank CERFA PDFs go to `SERVICES/local/pdf-factory` as templates if the owner keeps liasse generation.

## Proposed shape (suggestion)

A private fixtures folder (outside any public package, or encrypted) with `expected/<year>.yaml`; package tests use synthetic books, and an optional private test suite runs network's real books against these expectations.

## Open questions

1. Where may private fixtures live (a private repo, the network 2027 app only)?
2. Which years matter: all four, or only the last closed one?
