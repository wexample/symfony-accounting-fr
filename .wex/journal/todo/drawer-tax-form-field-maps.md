# French tax forms: backgrounds + declarative field maps (2033, 3517, 2065, 2572, 3514, 1330)

Opened: 2026-10-09
Updated: 2026-10-09
Author: agent:archeology-drawers

## Read this first — status of this todo

> **This is a proposal for discussion, not an order to code.** It comes from the 2026-10 "bottom of the drawers" archaeology pass. Read it, then discuss it with the owner: every design choice below is to be challenged and validated **before** any code is written. Do not start implementing on your own.
>
> - **Before anything else**: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/PACKAGES/PHP/.wex/local/package-agent-intro.md` — where you stand in the suite, what a package must look like, how to work, and how to judge a drawer todo.
> - Context: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/local/network/.wex/knowledge/readme/archeology/index.md.j2`, `sources.md.j2`, and the drawers page `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/local/network/.wex/knowledge/readme/archeology/drawers-backend.md.j2`.
> - Safety: `NETWORK/local/network` runs on production data — read its code only, never run anything against it. Anonymize anything taken from it. Never copy secrets found in its history.

## What

network fills official tax forms by drawing values at fixed mm positions over blank form images. ~150–160 placements exist in PHP (2018–2020 editions really produced; later years broken). They can be turned into a declarative field map per form edition, the box values coming from this package's existing data providers (`FrForm2033DataProvider`, `FrCa12Form`, `FrCa3Form`).

## Sources

- Backgrounds: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/local/network/assets/pdf/accounting/**` (62 JPEG pages; naming `<form>-sd_<impots.gouv id>-<page>.jpg`).
- Placements: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/local/network/src/Pdf/Accounting2018..2024/**/Page/*.php`; base `AccountingPdfPage`; resolution in `src/Service/Pdf/AccountingFormsPdfService.php`.
- Box formulas: `src/Pdf/Accounting2018/BalanceSheet/Form2033.php` (see drawers-backend page for the boxes missing from `accounting-reports`).
- Deleted: CA3 backgrounds (`git -C /home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/repo.git log --all -- 'project/assets/pdf/accounting/vat/*/CA3*'`), blank 2050–2059 returns (`git -C /home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/repo.git show dc85d9b12^:project/assets/pdf/accounting/fr-2018-tax-return.pdf`).

## Proposed shape (to challenge)

1. Extract placements mechanically into YAML `form/edition/pages/fields{key,x,y,w,h,align,format,comb}` — a script over the PHP calls.
2. Rendering goes to pdf-factory (overlay on background, or filling the official AcroForm PDF for new editions — preferred).
3. Calibrate against the private filled outputs (`var/accounting/**`, never committed).

## Do not

Copy the signature PNGs, `rib-wex.pdf`, `memento-wex.pdf`, or any filled output. Port the 2035 forms (not applicable to an IS association).

## Open questions

Which forms does the owner still file himself vs the accountant? CA12 or CA3 (see recap decisions)?
