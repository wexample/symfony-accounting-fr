<?php

/*
 * French tax return of the normal regime, form 2053: exceptional income and
 * charges, employee profit-sharing and income tax. The totals and the result
 * (HL, HM, HN) span 2052 and 2053: FrLiasseDataProvider computes them. Check the
 * codes against the form of the year.
 */
return [
    'key' => 'liasse_2053',
    'label' => 'report.fr.liasse_2053',
    'lines' => [
        ['key' => 'HA', 'label' => 'report.fr.liasse.HA', 'accounts' => ['771'], 'sign' => -1, 'level' => 1],
        ['key' => 'HC', 'label' => 'report.fr.liasse.HC', 'accounts' => ['787', '797'], 'sign' => -1, 'level' => 1],
        ['key' => 'HB', 'label' => 'report.fr.liasse.HB', 'accounts' => ['77'], 'sign' => -1, 'level' => 1],
        ['key' => 'HD', 'label' => 'report.fr.liasse.HD', 'formula' => 'HA+HB+HC'],
        ['key' => 'HE', 'label' => 'report.fr.liasse.HE', 'accounts' => ['671'], 'sign' => 1, 'level' => 1],
        ['key' => 'HG', 'label' => 'report.fr.liasse.HG', 'accounts' => ['687'], 'sign' => 1, 'level' => 1],
        ['key' => 'HF', 'label' => 'report.fr.liasse.HF', 'accounts' => ['67'], 'sign' => 1, 'level' => 1],
        ['key' => 'HH', 'label' => 'report.fr.liasse.HH', 'formula' => 'HE+HF+HG'],
        ['key' => 'HI', 'label' => 'report.fr.liasse.HI', 'formula' => 'HD-HH'],
        ['key' => 'HJ', 'label' => 'report.fr.liasse.HJ', 'accounts' => ['691'], 'sign' => 1, 'level' => 1],
        ['key' => 'HK', 'label' => 'report.fr.liasse.HK', 'accounts' => ['69'], 'sign' => 1, 'level' => 1],
    ],
];
