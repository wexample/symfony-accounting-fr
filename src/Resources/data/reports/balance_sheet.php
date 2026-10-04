<?php

/*
 * French balance sheet, in the layout of form 2033-A (simplified regime).
 * Accounts go to the first line whose rules take them; "D"/"C" restrict a rule
 * to debit/credit balances (512 is cash when positive, an overdraft otherwise).
 * The catch-all lines at the end of each side make sure no account is lost.
 */
return [
    'key' => 'balance_sheet',
    'label' => 'report.balance_sheet',
    'lines' => [
        ['key' => 'intangible_assets', 'label' => 'report.fr.intangible_assets', 'accounts' => ['20'], 'deduct' => ['280', '290'], 'level' => 1],
        ['key' => 'tangible_assets', 'label' => 'report.fr.tangible_assets', 'accounts' => ['21', '22', '23'], 'deduct' => ['281', '282', '291', '292', '293'], 'level' => 1],
        ['key' => 'financial_assets', 'label' => 'report.fr.financial_assets', 'accounts' => ['26', '27'], 'deduct' => ['296', '297'], 'level' => 1],
        ['key' => 'fixed_assets', 'label' => 'report.fixed_assets', 'formula' => 'intangible_assets+tangible_assets+financial_assets'],
        ['key' => 'stocks', 'label' => 'report.stocks', 'accounts' => ['31', '32', '33', '34', '35', '37'], 'deduct' => ['39'], 'level' => 1],
        ['key' => 'advances_paid', 'label' => 'report.fr.advances_paid', 'accounts' => ['4091'], 'level' => 1],
        ['key' => 'trade_receivables', 'label' => 'report.fr.trade_receivables', 'accounts' => ['411', '413', '416', '417', '418'], 'deduct' => ['491'], 'level' => 1],
        ['key' => 'other_receivables', 'label' => 'report.fr.other_receivables', 'accounts' => ['4D'], 'deduct' => ['495', '496'], 'level' => 1],
        ['key' => 'investments', 'label' => 'report.fr.investments', 'accounts' => ['50'], 'deduct' => ['59'], 'level' => 1],
        ['key' => 'cash', 'label' => 'report.cash', 'accounts' => ['51D', '52D', '53', '54', '58D'], 'level' => 1],
        ['key' => 'prepaid_expenses', 'label' => 'report.fr.prepaid_expenses', 'accounts' => ['486'], 'level' => 1],
        ['key' => 'other_assets', 'label' => 'report.fr.other_assets', 'accounts' => ['2', '3', '5D'], 'level' => 1],
        ['key' => 'current_assets', 'label' => 'report.fr.current_assets', 'formula' => 'stocks+advances_paid+trade_receivables+other_receivables+investments+cash+prepaid_expenses+other_assets'],
        ['key' => 'assets', 'label' => 'report.assets', 'formula' => 'fixed_assets+current_assets'],

        ['key' => 'capital', 'label' => 'report.fr.capital', 'accounts' => ['101', '108'], 'sign' => -1, 'level' => 1],
        ['key' => 'reserves', 'label' => 'report.fr.reserves', 'accounts' => ['104', '105', '106', '107'], 'sign' => -1, 'level' => 1],
        ['key' => 'retained_earnings', 'label' => 'report.fr.retained_earnings', 'accounts' => ['11'], 'sign' => -1, 'level' => 1],
        ['key' => 'year_result', 'label' => 'report.year_result', 'accounts' => ['12', '6', '7'], 'sign' => -1, 'level' => 1],
        ['key' => 'grants', 'label' => 'report.fr.grants', 'accounts' => ['13'], 'sign' => -1, 'level' => 1],
        ['key' => 'regulated_provisions', 'label' => 'report.fr.regulated_provisions', 'accounts' => ['14'], 'sign' => -1, 'level' => 1],
        ['key' => 'equity', 'label' => 'report.equity', 'formula' => 'capital+reserves+retained_earnings+year_result+grants+regulated_provisions+other_equity'],
        ['key' => 'other_equity', 'label' => 'report.fr.other_equity', 'accounts' => ['10'], 'sign' => -1, 'level' => 1],
        ['key' => 'provisions', 'label' => 'report.provisions', 'accounts' => ['15'], 'sign' => -1, 'level' => 1],
        ['key' => 'borrowings', 'label' => 'report.fr.borrowings', 'accounts' => ['16', '17'], 'sign' => -1, 'level' => 1],
        ['key' => 'bank_overdrafts', 'label' => 'report.fr.bank_overdrafts', 'accounts' => ['51C', '52C'], 'sign' => -1, 'level' => 1],
        ['key' => 'advances_received', 'label' => 'report.fr.advances_received', 'accounts' => ['4191'], 'sign' => -1, 'level' => 1],
        ['key' => 'suppliers', 'label' => 'report.fr.suppliers', 'accounts' => ['40C'], 'sign' => -1, 'level' => 1],
        ['key' => 'tax_social_debts', 'label' => 'report.fr.tax_social_debts', 'accounts' => ['42C', '43C', '44C'], 'sign' => -1, 'level' => 1],
        ['key' => 'deferred_income', 'label' => 'report.fr.deferred_income', 'accounts' => ['487'], 'sign' => -1, 'level' => 1],
        ['key' => 'other_debts', 'label' => 'report.fr.other_debts', 'accounts' => ['1', '4C', '5C'], 'sign' => -1, 'level' => 1],
        ['key' => 'debts', 'label' => 'report.debts', 'formula' => 'borrowings+bank_overdrafts+advances_received+suppliers+tax_social_debts+deferred_income+other_debts'],
        ['key' => 'liabilities', 'label' => 'report.liabilities', 'formula' => 'equity+provisions+debts'],
    ],
];
