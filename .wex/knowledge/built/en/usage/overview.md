## Chart and accounts

`fr_pcg` (1 005 accounts) is loaded by default; `fr_pca` (associations, CRC 99-01) on demand: `bin/console accounting:chart-load <ledger> fr_pca`. Roles map to the usual accounts: 411/401, 706/707, 604/607, 512, 44571/44566, 44551/44567, 4452 for self-assessed VAT, 44574/44564 for VAT on payments, 4191 for deposits, 120/129 and 110/119.

## Invoices

Mentions follow the situation: franchise (art. 293 B), intra-EU services (autoliquidation, art. 283-2), intra-EU goods (262 ter I), export (262 I), exemption (261), the debits option for service providers who chose it, "EI" for sole traders, the RCS exemption, and on bills the late-payment terms (L441-10, D441-5). Settings: `fr_rcs_exempt`, `fr_vat_debits_option`, `late_penalty_rate`, `late_penalty_flat_fee`.

## FEC

```bash
bin/console accounting:export <ledger> 2026 fec          # 732829320FEC20261231.txt
bin/console accounting:import-entries <ledger> file.txt   # FEC detected; Debit/Credit or Montant/Sens
```

The export writes the 18 legal columns in order, auxiliary accounts and lettering included; `FecValidator` checks a file the way the administration's tool does. Options: `separator`, `encoding` (UTF-8 or ISO-8859-15), `account_length`.

## VAT and corporate tax

The national forms fill themselves from the core VAT return: `FrCa3Form` (monthly or quarterly normal regime), `FrCa12Form` (annual simplified regime, regime key `fr_ca12` or annual periodicity) with `FrCa12DepositCalculator` for the July and December deposits. `FrCorporateTaxService` computes the tax with the rates of each year since 2019 (reduced rate unless `fr_is_reduced_rate` is false), the four deposits, and books 695/444. `FrForm2033DataProvider` gives forms 2033-A and 2033-B by box.

Box numbers follow the forms network filled and the 2024 CA3: check them against the form of the year before filing.

## Bank files

`fr_lbp_2019` (CSV, and text copied from the PDF statements), `fr_lbp_2023` (CSV), `fr_ca_2023` (XLSX), detected automatically. `FrRibHelper` derives and checks RIB keys.
