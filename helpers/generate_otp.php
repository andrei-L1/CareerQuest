<?php
function generateOTP($length = 6) {
    $digits = '0123456789';
    $otp = '';
    
    for ($i = 0; $i < $length; $i++) {
        $otp .= $digits[random_int(0, strlen($digits) - 1)];
    }
    
    return $otp;
}