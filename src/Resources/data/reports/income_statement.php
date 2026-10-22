<?php

/*
 * French income statement, in the layout of form 2033-B. Groups follow the
 * official ones: operating (I, II), financial (V, VI), exceptional (VII, VIII),
 * profit sharing and income tax. Every 6/7 account lands somewhere: the
 * "other" lines take what no specific line took.
 */
return [
    'key' => 'income_statement',
    'label' => 'report.income_statement',
    'lines' => [
        ['key' => 'sales_goods', 'label' => 'report.fr.sales_goods', 'accounts' => ['707', '7097'], 'sign' => -1, 'level' => 1],
        ['key' => 'sales_services', 'label' => 'report.fr.sales_services', 'accounts' => ['70'], 'sign' => -1, 'level' => 1],
        ['key' => 'production', 'label' => 'report.fr.production', 'accounts' => ['71', '72'], 'sign' => -1, 'level' => 1],
        ['key' => 'operating_grants', 'label' => 'report.fr.operating_grants', 'accounts' => ['74'], 'sign' => -1, 'level' => 1],
        ['key' => 'other_operating_income', 'label' => 'report.fr.other_operating_income', 'accounts' => ['75!755', '781', '791'], 'sign' => -1, 'level' => 1],
        ['key' => 'operating_income', 'label' => 'report.fr.operating_income', 'formula' => 'sales_goods+sales_services+production+operating_grants+other_operating_income'],

        ['key' => 'purchases_goods', 'label' => 'report.fr.purchases_goods', 'accounts' => ['607', '6087', '6097'], 'level' => 1],
        ['key' => 'stock_change_goods', 'label' => 'report.fr.stock_change_goods', 'accounts' => ['6037'], 'level' => 1],
        ['key' => 'raw_materials', 'label' => 'report.fr.raw_materials', 'accounts' => ['601', '602', '6081', '6082', '6091', '6092'], 'level' => 1],
        ['key' => 'stock_change_raw', 'label' => 'report.fr.stock_change_raw', 'accounts' => ['603'], 'level' => 1],
        ['key' => 'external_charges', 'label' => 'report.fr.external_charges', 'accounts' => ['604', '605', '606', '608', '609', '61', '62'], 'level' => 1],
        ['key' => 'taxes', 'label' => 'report.fr.taxes', 'accounts' => ['63'], 'level' => 1],
        ['key' => 'wages', 'label' => 'report.fr.wages', 'accounts' => ['641', '642', '643', '644', '648'], 'level' => 1],
        ['key' => 'social_charges', 'label' => 'report.fr.social_charges', 'accounts' => ['645', '646', '647'], 'level' => 1],
        ['key' => 'depreciation', 'label' => 'report.fr.depreciation', 'accounts' => ['681'], 'level' => 1],
        ['key' => 'other_operating_charges', 'label' => 'report.fr.other_operating_charges', 'accounts' => ['65!655'], 'level' => 1],
        ['key' => 'operating_charges', 'label' => 'report.fr.operating_charges', 'formula' => 'purchases_goods+stock_change_goods+raw_materials+stock_change_raw+external_charges+taxes+wages+social_charges+depreciation+other_operating_charges'],
        ['key' => 'operating_result', 'label' => 'report.fr.operating_result', 'formula' => 'operating_income-operating_charges'],

        ['key' => 'joint_operations', 'label' => 'report.fr.joint_operations', 'formula' => 'joint_income-joint_charges'],
        ['key' => 'joint_income', 'label' => 'report.fr.joint_income', 'accounts' => ['755'], 'sign' => -1, 'level' => 1],
        ['key' => 'joint_charges', 'label' => 'report.fr.joint_charges', 'accounts' => ['655'], 'level' => 1],

        ['key' => 'financial_income', 'label' => 'report.fr.financial_income', 'accounts' => ['76', '786', '796'], 'sign' => -1, 'level' => 1],
        ['key' => 'financial_charges', 'label' => 'report.fr.financial_charges', 'accounts' => ['66', '686'], 'level' => 1],
        ['key' => 'financial_result', 'label' => 'report.fr.financial_result', 'formula' => 'financial_income-financial_charges'],
        ['key' => 'current_result', 'label' => 'report.fr.current_result', 'formula' => 'operating_result+joint_income-joint_charges+financial_result'],

        ['key' => 'exceptional_income', 'label' => 'report.fr.exceptional_income', 'accounts' => ['77', '787', '797'], 'sign' => -1, 'level' => 1],
        ['key' => 'exceptional_charges', 'label' => 'report.fr.exceptional_charges', 'accounts' => ['67', '687'], 'level' => 1],
        ['key' => 'exceptional_result', 'label' => 'report.fr.exceptional_result', 'formula' => 'exceptional_income-exceptional_charges'],

        ['key' => 'profit_sharing', 'label' => 'report.fr.profit_sharing', 'accounts' => ['691'], 'level' => 1],
        ['key' => 'income_tax', 'label' => 'report.fr.income_tax', 'accounts' => ['695', '696', '697', '698', '699'], 'level' => 1],

        ['key' => 'other_income', 'label' => 'report.fr.other_income', 'accounts' => ['7'], 'sign' => -1, 'level' => 1],
        ['key' => 'other_charges', 'label' => 'report.fr.other_charges', 'accounts' => ['6'], 'level' => 1],

        ['key' => 'income', 'label' => 'report.income', 'formula' => 'operating_income+joint_income+financial_income+exceptional_income+other_income'],
        ['key' => 'charges', 'label' => 'report.charges', 'formula' => 'operating_charges+joint_charges+financial_charges+exceptional_charges+profit_sharing+income_tax+other_charges'],
        ['key' => 'result', 'label' => 'report.result', 'formula' => 'income-charges'],
    ],
];
