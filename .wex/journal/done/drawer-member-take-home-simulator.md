# Member take-home simulator (URSSAF charges, reserve, expenses) from the 2018 ancestor

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

In the 2018 ancestor app, each invoice and each month showed what a member actually keeps: base + VAT = total, minus URSSAF micro-entrepreneur charges, minus a 10 % reserve, plus re-billed expenses. A button marked every invoice paid in a month as "declared to URSSAF". Never ported to network.

## Sources

- `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/outside/wexample_invoices/project/class/website.inc` (`invoiceTotal`, `totalDetail`, constants at the top)
- `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/outside/wexample_invoices/project/pages/invoice-total.inc.php`, `invoices-validate-all.inc.php`, `invoices.inc.php`
- last commit `ab7e961` (2018-06-11). The repo history contains secrets and real data: read code only.

## Proposed shape (to challenge)

Rates as dated configuration (not constants), a calculator service in this package, and a view per member/month — related to the cooperative's own fee (3 %) and to #78 (integrated calculator, see `drawer-invoice-ux-backlog.md` in symfony-accounting).

## Open questions

Is this useful for members today (cooperative fee + their own status), and for which statuses besides micro-entrepreneur?

## Classé (2026-10-13)

Abandonné : plus de membres qui facturent via la coopérative. Le moteur sert d'abord à la société individuelle du propriétaire.
