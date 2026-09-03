<?php
if (!function_exists('esteem_meta_normalize_phone')) {
    function esteem_meta_normalize_phone($phone) {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (substr($digits, 0, 2) === '00') $digits = substr($digits, 2);
        if (substr($digits, 0, 2) === '61') $digits = '0' . substr($digits, 2);
        if (strlen($digits) === 9 && substr($digits, 0, 1) === '4') $digits = '0' . $digits;
        return strlen($digits) >= 9 ? $digits : '';
    }
}
if (!function_exists('esteem_meta_setting_secret')) {
    function esteem_meta_setting_secret($value, $key) {
        return $value ? encode_id($value, 'esteem_meta_' . $key) : '';
    }
}
if (!function_exists('esteem_meta_setting_unsecret')) {
    function esteem_meta_setting_unsecret($value, $key) {
        return $value ? decode_id($value, 'esteem_meta_' . $key) : '';
    }
}
