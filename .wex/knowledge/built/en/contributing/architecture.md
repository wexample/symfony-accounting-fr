## Architecture

src/Service/FrJurisdiction.php extends the core `AbstractJurisdiction`: charts are read from src/Resources/data/charts (CSV converted from network's migrations), statement layouts from src/Resources/data/reports (2033-A/B structure: an account goes to the first line taking it, catch-all lines last so nothing is lost). Mention texts are translations (src/Resources/translations/accounting.fr.yaml).

Everything else plugs into a core extension point: src/Service/Exchange/FecExporter.php is a `LedgerExporterInterface`, src/Service/Exchange/FecImporter.php an `EntryImporterInterface`, the forms in src/Service/Vat are `VatReturnFormInterface`, the parsers in src/Service/Bank/Parser are `BankStatementParserInterface`. Values that change by year (IS rates) are data keyed by the year they apply from.

Tests run on synthetic fixtures; network's real FEC files were only read, never copied.
