<?php

/*
 * French tax return of the normal regime, form 2052: operating and financial
 * income and charges. Keys are the form's box codes; sales are all reported in
 * the France column (FA, FD, FG), exports being part of it. Check the codes
 * against the form of the year. The last financial lines (GL, GR) catch what no
 * other line of 2052 takes, the exceptional accounts (77, 787, 797, 67, 687, 69)
 * being left to 2053.
 */
return [
    'key' => 'liasse_2052',
    'label' => 'report.fr.liasse_2052',
    'lines' => [
        ['key' => 'FA', 'label' => 'report.fr.liasse.FA', 'accounts' => ['707', '7097'], 'sign' => -1, 'level' => 1],
        ['key' => 'FD', 'label' => 'report.fr.liasse.FD', 'accounts' => ['701', '702', '703', '7091', '7092'], 'sign' => -1, 'level' => 1],
        ['key' => 'FG', 'label' => 'report.fr.liasse.FG', 'accounts' => ['704', '705', '706', '708', '709', '70'], 'sign' => -1, 'level' => 1],
        ['key' => 'FL', 'label' => 'report.fr.liasse.FL', 'formula' => 'FA+FD+FG'],
        ['key' => 'FM', 'label' => 'report.fr.liasse.FM', 'accounts' => ['713', '71'], 'sign' => -1, 'level' => 1],
        ['key' => 'FN', 'label' => 'report.fr.liasse.FN', 'accounts' => ['72', '73'], 'sign' => -1, 'level' => 1],
        ['key' => 'FO', 'label' => 'report.fr.liasse.FO', 'accounts' => ['74'], 'sign' => -1, 'level' => 1],
        ['key' => 'FP', 'label' => 'report.fr.liasse.FP', 'accounts' => ['781', '791'], 'sign' => -1, 'level' => 1],
        ['key' => 'FQ', 'label' => 'report.fr.liasse.FQ', 'accounts' => ['75!755'], 'sign' => -1, 'level' => 1],
        ['key' => 'FR', 'label' => 'report.fr.liasse.FR', 'formula' => 'FL+FM+FN+FO+FP+FQ'],
        ['key' => 'FS', 'label' => 'report.fr.liasse.FS', 'accounts' => ['607', '6087', '6097'], 'sign' => 1, 'level' => 1],
        ['key' => 'FT', 'label' => 'report.fr.liasse.FT', 'accounts' => ['6037'], 'sign' => 1, 'level' => 1],
        ['key' => 'FU', 'label' => 'report.fr.liasse.FU', 'accounts' => ['601', '602', '6081', '6082', '6091', '6092'], 'sign' => 1, 'level' => 1],
        ['key' => 'FV', 'label' => 'report.fr.liasse.FV', 'accounts' => ['6031', '6032'], 'sign' => 1, 'level' => 1],
        ['key' => 'FW', 'label' => 'report.fr.liasse.FW', 'accounts' => ['60', '61', '62'], 'sign' => 1, 'level' => 1],
        ['key' => 'FX', 'label' => 'report.fr.liasse.FX', 'accounts' => ['63'], 'sign' => 1, 'level' => 1],
        ['key' => 'FY', 'label' => 'report.fr.liasse.FY', 'accounts' => ['641', '644'], 'sign' => 1, 'level' => 1],
        ['key' => 'FZ', 'label' => 'report.fr.liasse.FZ', 'accounts' => ['645', '646', '647', '648'], 'sign' => 1, 'level' => 1],
        ['key' => 'GA', 'label' => 'report.fr.liasse.GA', 'accounts' => ['6811', '6812'], 'sign' => 1, 'level' => 1],
        ['key' => 'GB', 'label' => 'report.fr.liasse.GB', 'accounts' => ['6816'], 'sign' => 1, 'level' => 1],
        ['key' => 'GC', 'label' => 'report.fr.liasse.GC', 'accounts' => ['6817'], 'sign' => 1, 'level' => 1],
        ['key' => 'GD', 'label' => 'report.fr.liasse.GD', 'accounts' => ['6815', '681'], 'sign' => 1, 'level' => 1],
        ['key' => 'GE', 'label' => 'report.fr.liasse.GE', 'accounts' => ['65!655', '64'], 'sign' => 1, 'level' => 1],
        ['key' => 'GF', 'label' => 'report.fr.liasse.GF', 'formula' => 'FS+FT+FU+FV+FW+FX+FY+FZ+GA+GB+GC+GD+GE'],
        ['key' => 'GG', 'label' => 'report.fr.liasse.GG', 'formula' => 'FR-GF'],
        ['key' => 'GH', 'label' => 'report.fr.liasse.GH', 'accounts' => ['755'], 'sign' => -1, 'level' => 1],
        ['key' => 'GI', 'label' => 'report.fr.liasse.GI', 'accounts' => ['655'], 'sign' => 1, 'level' => 1],
        ['key' => 'GJ', 'label' => 'report.fr.liasse.GJ', 'accounts' => ['761'], 'sign' => -1, 'level' => 1],
        ['key' => 'GK', 'label' => 'report.fr.liasse.GK', 'accounts' => ['762'], 'sign' => -1, 'level' => 1],
        ['key' => 'GM', 'label' => 'report.fr.liasse.GM', 'accounts' => ['786', '796'], 'sign' => -1, 'level' => 1],
        ['key' => 'GN', 'label' => 'report.fr.liasse.GN', 'accounts' => ['766'], 'sign' => -1, 'level' => 1],
        ['key' => 'GO', 'label' => 'report.fr.liasse.GO', 'accounts' => ['767'], 'sign' => -1, 'level' => 1],
        ['key' => 'GL', 'label' => 'report.fr.liasse.GL', 'accounts' => ['763', '764', '765', '768', '76', '7!77!787!797'], 'sign' => -1, 'level' => 1],
        ['key' => 'GP', 'label' => 'report.fr.liasse.GP', 'formula' => 'GJ+GK+GL+GM+GN+GO'],
        ['key' => 'GQ', 'label' => 'report.fr.liasse.GQ', 'accounts' => ['686'], 'sign' => 1, 'level' => 1],
        ['key' => 'GS', 'label' => 'report.fr.liasse.GS', 'accounts' => ['666'], 'sign' => 1, 'level' => 1],
        ['key' => 'GT', 'label' => 'report.fr.liasse.GT', 'accounts' => ['667'], 'sign' => 1, 'level' => 1],
        ['key' => 'GR', 'label' => 'report.fr.liasse.GR', 'accounts' => ['66', '6!67!687!69'], 'sign' => 1, 'level' => 1],
        ['key' => 'GU', 'label' => 'report.fr.liasse.GU', 'formula' => 'GQ+GR+GS+GT'],
        ['key' => 'GV', 'label' => 'report.fr.liasse.GV', 'formula' => 'GP-GU'],
        ['key' => 'GW', 'label' => 'report.fr.liasse.GW', 'formula' => 'GG+GH-GI+GV'],
    ],
];
