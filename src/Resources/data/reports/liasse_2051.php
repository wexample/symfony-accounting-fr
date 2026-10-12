<?php

/*
 * French tax return of the normal regime, form 2051: the balance sheet,
 * liabilities. Keys are the form's box codes. The year's result (DI) counts the
 * income and charges not closed yet. Check the codes against the form of the year.
 */
return [
    'key' => 'liasse_2051',
    'label' => 'report.fr.liasse_2051',
    'lines' => [
        ['key' => 'DA', 'label' => 'report.fr.liasse.DA', 'accounts' => ['101', '108'], 'sign' => -1, 'level' => 1],
        ['key' => 'DB', 'label' => 'report.fr.liasse.DB', 'accounts' => ['104'], 'sign' => -1, 'level' => 1],
        ['key' => 'DC', 'label' => 'report.fr.liasse.DC', 'accounts' => ['105'], 'sign' => -1, 'level' => 1],
        ['key' => 'DD', 'label' => 'report.fr.liasse.DD', 'accounts' => ['1061'], 'sign' => -1, 'level' => 1],
        ['key' => 'DE', 'label' => 'report.fr.liasse.DE', 'accounts' => ['1063'], 'sign' => -1, 'level' => 1],
        ['key' => 'DF', 'label' => 'report.fr.liasse.DF', 'accounts' => ['1064'], 'sign' => -1, 'level' => 1],
        ['key' => 'DG', 'label' => 'report.fr.liasse.DG', 'accounts' => ['106', '107', '10!109'], 'sign' => -1, 'level' => 1],
        ['key' => 'DH', 'label' => 'report.fr.liasse.DH', 'accounts' => ['11'], 'sign' => -1, 'level' => 1],
        ['key' => 'DI', 'label' => 'report.fr.liasse.DI', 'accounts' => ['12', '6', '7'], 'sign' => -1, 'level' => 1],
        ['key' => 'DJ', 'label' => 'report.fr.liasse.DJ', 'accounts' => ['13'], 'sign' => -1, 'level' => 1],
        ['key' => 'DK', 'label' => 'report.fr.liasse.DK', 'accounts' => ['14'], 'sign' => -1, 'level' => 1],
        ['key' => 'DL', 'label' => 'report.fr.liasse.DL', 'formula' => 'DA+DB+DC+DD+DE+DF+DG+DH+DI+DJ+DK'],
        ['key' => 'DM', 'label' => 'report.fr.liasse.DM', 'accounts' => ['1671'], 'sign' => -1, 'level' => 1],
        ['key' => 'DN', 'label' => 'report.fr.liasse.DN', 'accounts' => ['1674'], 'sign' => -1, 'level' => 1],
        ['key' => 'DO', 'label' => 'report.fr.liasse.DO', 'formula' => 'DM+DN'],
        ['key' => 'DP', 'label' => 'report.fr.liasse.DP', 'accounts' => ['151'], 'sign' => -1, 'level' => 1],
        ['key' => 'DQ', 'label' => 'report.fr.liasse.DQ', 'accounts' => ['15'], 'sign' => -1, 'level' => 1],
        ['key' => 'DR', 'label' => 'report.fr.liasse.DR', 'formula' => 'DP+DQ'],
        ['key' => 'DS', 'label' => 'report.fr.liasse.DS', 'accounts' => ['161'], 'sign' => -1, 'level' => 1],
        ['key' => 'DT', 'label' => 'report.fr.liasse.DT', 'accounts' => ['163'], 'sign' => -1, 'level' => 1],
        ['key' => 'DU', 'label' => 'report.fr.liasse.DU', 'accounts' => ['164', '51C', '52C', '519'], 'sign' => -1, 'level' => 1],
        ['key' => 'DV', 'label' => 'report.fr.liasse.DV', 'accounts' => ['16!169', '17', '1!109'], 'sign' => -1, 'level' => 1],
        ['key' => 'DW', 'label' => 'report.fr.liasse.DW', 'accounts' => ['4191'], 'sign' => -1, 'level' => 1],
        ['key' => 'DZ', 'label' => 'report.fr.liasse.DZ', 'accounts' => ['404', '405'], 'sign' => -1, 'level' => 1],
        ['key' => 'DX', 'label' => 'report.fr.liasse.DX', 'accounts' => ['40C'], 'sign' => -1, 'level' => 1],
        ['key' => 'DY', 'label' => 'report.fr.liasse.DY', 'accounts' => ['42C', '43C', '44C'], 'sign' => -1, 'level' => 1],
        ['key' => 'EB', 'label' => 'report.fr.liasse.EB', 'accounts' => ['487'], 'sign' => -1, 'level' => 1],
        ['key' => 'ED', 'label' => 'report.fr.liasse.ED', 'accounts' => ['477'], 'sign' => -1, 'level' => 1],
        ['key' => 'EA', 'label' => 'report.fr.liasse.EA', 'accounts' => ['4C', '5C'], 'sign' => -1, 'level' => 1],
        ['key' => 'EC', 'label' => 'report.fr.liasse.EC', 'formula' => 'DS+DT+DU+DV+DW+DX+DY+DZ+EA+EB'],
        ['key' => 'EE', 'label' => 'report.fr.liasse.EE', 'formula' => 'DL+DO+DR+EC+ED'],
    ],
];
