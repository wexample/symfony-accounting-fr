<?php

/*
 * French tax return of the normal regime, form 2050: the balance sheet, assets.
 * Keys are the form's box codes; each line has its gross (column 1), its
 * depreciation or provision (column 2) and its net (column 3, `amount`).
 * Accounts go to the first line whose rules take them, so specific lines come
 * before the catch-all ones. Check the codes against the form of the year.
 */
return [
    'key' => 'liasse_2050',
    'label' => 'report.fr.liasse_2050',
    'lines' => [
        ['key' => 'AA', 'label' => 'report.fr.liasse.AA', 'accounts' => ['109'], 'level' => 1],
        ['key' => 'AB', 'label' => 'report.fr.liasse.AB', 'accounts' => ['201'], 'deduct' => ['2801'], 'level' => 1],
        ['key' => 'CX', 'label' => 'report.fr.liasse.CX', 'accounts' => ['203'], 'deduct' => ['2803'], 'level' => 1],
        ['key' => 'AF', 'label' => 'report.fr.liasse.AF', 'accounts' => ['205'], 'deduct' => ['2805', '2905'], 'level' => 1],
        ['key' => 'AH', 'label' => 'report.fr.liasse.AH', 'accounts' => ['206', '207'], 'deduct' => ['2807', '2906', '2907'], 'level' => 1],
        ['key' => 'AJ', 'label' => 'report.fr.liasse.AJ', 'accounts' => ['20!280!290'], 'deduct' => ['280', '290'], 'level' => 1],
        ['key' => 'AL', 'label' => 'report.fr.liasse.AL', 'accounts' => ['237'], 'deduct' => ['2932'], 'level' => 1],
        ['key' => 'AN', 'label' => 'report.fr.liasse.AN', 'accounts' => ['211', '212'], 'deduct' => ['2811', '2812', '2911'], 'level' => 1],
        ['key' => 'AP', 'label' => 'report.fr.liasse.AP', 'accounts' => ['213', '214'], 'deduct' => ['2813', '2814'], 'level' => 1],
        ['key' => 'AR', 'label' => 'report.fr.liasse.AR', 'accounts' => ['215'], 'deduct' => ['2815'], 'level' => 1],
        ['key' => 'AT', 'label' => 'report.fr.liasse.AT', 'accounts' => ['21!28!29', '22', '24', '25'], 'deduct' => ['281', '282', '291', '292'], 'level' => 1],
        ['key' => 'AV', 'label' => 'report.fr.liasse.AV', 'accounts' => ['231', '232'], 'deduct' => ['2931'], 'level' => 1],
        ['key' => 'AX', 'label' => 'report.fr.liasse.AX', 'accounts' => ['238', '23!28!29'], 'deduct' => ['293'], 'level' => 1],
        ['key' => 'CU', 'label' => 'report.fr.liasse.CU', 'accounts' => ['261', '266'], 'deduct' => ['2961', '2966'], 'level' => 1],
        ['key' => 'BB', 'label' => 'report.fr.liasse.BB', 'accounts' => ['267', '268'], 'deduct' => ['2967', '2968'], 'level' => 1],
        ['key' => 'BD', 'label' => 'report.fr.liasse.BD', 'accounts' => ['271', '272', '273'], 'deduct' => ['2971', '2972', '2973'], 'level' => 1],
        ['key' => 'BF', 'label' => 'report.fr.liasse.BF', 'accounts' => ['274'], 'deduct' => ['2974'], 'level' => 1],
        ['key' => 'BH', 'label' => 'report.fr.liasse.BH', 'accounts' => ['27!28!29', '26!28!29', '2!28!29'], 'deduct' => ['297', '296', '28', '29'], 'level' => 1],
        ['key' => 'BJ', 'label' => 'report.fr.liasse.BJ', 'formula' => 'AB+CX+AF+AH+AJ+AL+AN+AP+AR+AT+AV+AX+CU+BB+BD+BF+BH'],
        ['key' => 'BL', 'label' => 'report.fr.liasse.BL', 'accounts' => ['31', '32'], 'deduct' => ['391', '392'], 'level' => 1],
        ['key' => 'BN', 'label' => 'report.fr.liasse.BN', 'accounts' => ['33'], 'deduct' => ['393'], 'level' => 1],
        ['key' => 'BP', 'label' => 'report.fr.liasse.BP', 'accounts' => ['34'], 'deduct' => ['394'], 'level' => 1],
        ['key' => 'BR', 'label' => 'report.fr.liasse.BR', 'accounts' => ['35'], 'deduct' => ['395'], 'level' => 1],
        ['key' => 'BT', 'label' => 'report.fr.liasse.BT', 'accounts' => ['37', '3!39'], 'deduct' => ['397', '39'], 'level' => 1],
        ['key' => 'BV', 'label' => 'report.fr.liasse.BV', 'accounts' => ['4091'], 'level' => 1],
        ['key' => 'BX', 'label' => 'report.fr.liasse.BX', 'accounts' => ['411', '413', '416', '417', '418'], 'deduct' => ['491'], 'level' => 1],
        ['key' => 'CB', 'label' => 'report.fr.liasse.CB', 'accounts' => ['4562'], 'level' => 1],
        ['key' => 'CH', 'label' => 'report.fr.liasse.CH', 'accounts' => ['486'], 'level' => 1],
        ['key' => 'CN', 'label' => 'report.fr.liasse.CN', 'accounts' => ['476'], 'level' => 1],
        ['key' => 'BZ', 'label' => 'report.fr.liasse.BZ', 'accounts' => ['4D'], 'deduct' => ['495', '496'], 'level' => 1],
        ['key' => 'CD', 'label' => 'report.fr.liasse.CD', 'accounts' => ['50!509'], 'deduct' => ['59'], 'level' => 1],
        ['key' => 'CF', 'label' => 'report.fr.liasse.CF', 'accounts' => ['51D', '52D', '53', '54', '58D', '5D'], 'level' => 1],
        ['key' => 'CJ', 'label' => 'report.fr.liasse.CJ', 'formula' => 'BL+BN+BP+BR+BT+BV+BX+BZ+CB+CD+CF+CH'],
        ['key' => 'CL', 'label' => 'report.fr.liasse.CL', 'accounts' => ['481'], 'level' => 1],
        ['key' => 'CM', 'label' => 'report.fr.liasse.CM', 'accounts' => ['169'], 'level' => 1],
        ['key' => 'CO', 'label' => 'report.fr.liasse.CO', 'formula' => 'AA+BJ+CJ+CL+CM+CN'],
    ],
];
