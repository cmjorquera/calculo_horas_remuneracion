<?php

if (!function_exists('hhmm')) {
    function hhmm($hoursFloat)
    {
        $totalMin = (int)round(((float)$hoursFloat) * 60);
        $h = floor($totalMin / 60);
        $m = $totalMin % 60;

        return str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ":" . str_pad((string)$m, 2, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('minutosAHHMM')) {
    function minutosAHHMM($totalMin)
    {
        $totalMin = max(0, (int)$totalMin);
        $h = floor($totalMin / 60);
        $m = $totalMin % 60;

        return str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ":" . str_pad((string)$m, 2, '0', STR_PAD_LEFT);
    }
}
