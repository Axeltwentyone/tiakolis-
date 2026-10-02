<?php

if (! function_exists('fcfa')) {
    /** 12500 → « 12 500 FCFA » */
    function fcfa(int|float|null $montant): string
    {
        return number_format((float) $montant, 0, ',', ' ').' FCFA';
    }
}
