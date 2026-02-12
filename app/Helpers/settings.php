<?php

use App\Models\Setting;

if (!function_exists('hotel')) {
    function hotel($key, $default = null)
    {
        try {
            return Setting::get($key, $default);
        } catch (\Exception $e) {
            return $default;
        }
    }
}
