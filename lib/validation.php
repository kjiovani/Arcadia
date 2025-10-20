<?php
// lib/validation.php
function str_trim($s)
{
    return trim((string) $s);
}
function required($v, $label)
{
    if ($v === '') {
        throw new Exception("$label wajib diisi");
    }
    return $v;
}
function positive_int($v, $label)
{
    $n = (int) $v;
    if ($n <= 0) {
        throw new Exception("$label harus bilangan > 0");
    }
    return $n;
}
?>