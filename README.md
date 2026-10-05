# symfony-accounting-fr

Version: 2.0.0

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

## Table of Contents

- [Chart and accounts](#chart-and-accounts)
- [Invoices](#invoices)
- [FEC](#fec)
- [VAT and corporate tax](#vat-and-corporate-tax)
- [Bank files](#bank-files)
- [Architecture](#architecture)
- [Integration in the Suite](#integration-in-the-suite)
- [Dependencies](#dependencies)
- [Versioning & Compatibility Policy](#versioning--compatibility-policy)
- [License](#license)
- [About us](#about-us)
- [Migration Notes](#migration-notes)

## Architecture

src/Service/FrJurisdiction.php extends the core `AbstractJurisdiction`: charts are read from src/Resources/data/charts (CSV converted from network's migrations), statement layouts from src/Resources/data/reports (2033-A/B structure: an account goes to the first line taking it, catch-all lines last so nothing is lost). Mention texts are translations (src/Resources/translations/accounting.fr.yaml).

Everything else plugs into a core extension point: src/Service/Exchange/FecExporter.php is a `LedgerExporterInterface`, src/Service/Exchange/FecImporter.php an `EntryImporterInterface`, the forms in src/Service/Vat are `VatReturnFormInterface`, the parsers in src/Service/Bank/Parser are `BankStatementParserInterface`. Values that change by year (IS rates) are data keyed by the year they apply from.

Tests run on synthetic fixtures; network's real FEC files were only read, never copied.

## Integration in the Suite

This package is part of the Wexample Suite — a collection of high-quality, modular tools designed to work seamlessly together across multiple languages and environments.

### Related Packages

The suite includes packages for configuration management, file handling, prompts, and more. Each package can be used independently or as part of the integrated suite.

Visit the [Wexample Suite documentation](https://docs.wexample.com) for the complete package ecosystem.

## Dependencies

- php: >=8.5
- wexample/symfony-accounting: >=4.0.0
- wexample/symfony-helpers: >=14.0.0
- phpoffice/phpspreadsheet: ^2.0 || ^3.0 || ^4.0 || ^5.0

## Versioning & Compatibility Policy

Wexample packages follow **Semantic Versioning** (SemVer):

- **MAJOR**: Breaking changes
- **MINOR**: New features, backward compatible
- **PATCH**: Bug fixes, backward compatible

We maintain backward compatibility within major versions and provide clear migration guides for breaking changes.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Free to use in both personal and commercial projects.

## About us

[Wexample](https://wexample.com) stands as a cornerstone of the digital ecosystem — a collective of seasoned engineers, researchers, and creators driven by a relentless pursuit of technological excellence. More than a media platform, it has grown into a vibrant community where innovation meets craftsmanship, and where every line of code reflects a commitment to clarity, durability, and shared intelligence.

This packages suite embodies this spirit. Trusted by professionals and enthusiasts alike, it delivers a consistent, high-quality foundation for modern development — open, elegant, and battle-tested. Its reputation is built on years of collaboration, refinement, and rigorous attention to detail, making it a natural choice for those who demand both robustness and beauty in their tools.

Wexample cultivates a culture of mastery. Each package, each contribution carries the mark of a community that values precision, ethics, and innovation — a community proud to shape the future of digital craftsmanship.

## Migration Notes

When upgrading between major versions, refer to the migration guides in the documentation.

Breaking changes are clearly documented with upgrade paths and examples.
