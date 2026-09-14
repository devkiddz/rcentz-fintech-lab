<?php

use App\Helpers\AppHelper;

if (!function_exists('setting')) {
    /**
     * Get a setting value
     */
    function setting($key, $default = null)
    {
        return AppHelper::setting($key, $default);
    }
}

if (!function_exists('is_setting_enabled')) {
    /**
     * Check if a setting is enabled
     */
    function is_setting_enabled($key)
    {
        return AppHelper::isSettingEnabled($key);
    }
}

if (!function_exists('site_name')) {
    /**
     * Get site name
     */
    function site_name()
    {
        return AppHelper::siteName();
    }
}

if (!function_exists('site_email')) {
    /**
     * Get site email
     */
    function site_email()
    {
        return AppHelper::siteEmail();
    }
}

if (!function_exists('site_logo')) {
    /**
     * Get site logo URL
     */
    function site_logo()
    {
        return AppHelper::siteLogo();
    }
}

if (!function_exists('site_logo_light')) {
    function site_logo_light()
    {
        return AppHelper::siteLogoLight();
    }
}

if (!function_exists('site_logo_dark')) {
    function site_logo_dark()
    {
        return AppHelper::siteLogoDark();
    }
}

if (!function_exists('site_favicon')) {
    /**
     * Get site favicon URL
     */
    function site_favicon()
    {
        return AppHelper::siteFavicon();
    }
}

if (!function_exists('site_url')) {
    /**
     * Get site URL
     */
    function site_url()
    {
        return AppHelper::siteUrl();
    }
}

if (!function_exists('site_phone')) {
    /**
     * Get site phone
     */
    function site_phone()
    {
        return AppHelper::sitePhone();
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format currency with automatic user currency detection and conversion
     * 
     * @param float $amount The amount to format
     * @param string $fromCurrency The source currency (default: USD - all amounts in DB are in USD)
     * @param string|null $toCurrency The target currency (auto-detects from user if null)
     * @param \App\Models\User|null $user The user object (for email context)
     * @return string Formatted currency string with symbol
     */
    function format_currency($amount, $fromCurrency = 'USD', $toCurrency = null, $user = null)
    {
        return \App\Helpers\AppHelper::formatCurrency($amount, $fromCurrency, $toCurrency, $user);
    }
}

if (!function_exists('currency_symbol')) {
    /**
     * Get currency symbol for a given currency
     * 
     * @param string|null $currency Currency code (auto-detects from user if null)
     * @param \App\Models\User|null $user The user object (for email context)
     * @return string Currency symbol
     */
    function currency_symbol($currency = null, $user = null)
    {
        return \App\Helpers\AppHelper::getCurrencySymbol($currency, $user);
    }
}

if (!function_exists('user_currency')) {
    /**
     * Get authenticated user's currency preference
     * 
     * @return string Currency code (defaults to 'USD')
     */
    function user_currency()
    {
        return auth()->check() ? (auth()->user()->currency ?? 'USD') : 'USD';
    }
}

if (!function_exists('convert_currency')) {
    /**
     * Convert amount between currencies
     * 
     * @param float $amount Amount to convert
     * @param string $from Source currency code
     * @param string $to Target currency code
     * @return float Converted amount
     */
    function convert_currency($amount, $from = 'USD', $to = 'USD')
    {
        $service = new \App\Services\CurrencyService();
        return $service->convert($amount, $from, $to);
    }
}

if (!function_exists('format_percentage')) {
    /**
     * Format percentage
     */
    function format_percentage($value, $decimals = 2)
    {
        return AppHelper::formatPercentage($value, $decimals);
    }
}

if (!function_exists('format_number')) {
    /**
     * Format number with abbreviation
     */
    function format_number($number)
    {
        return AppHelper::formatNumber($number);
    }
}

if (!function_exists('get_initials')) {
    /**
     * Get initials from name
     */
    function get_initials($name)
    {
        return AppHelper::getInitials($name);
    }
}

if (!function_exists('generate_reference_id')) {
    /**
     * Generate a unique reference ID
     */
    function generate_reference_id($prefix = 'REF')
    {
        return AppHelper::generateReferenceId($prefix);
    }
}

if (!function_exists('get_status_badge_class')) {
    /**
     * Get status badge class
     */
    function get_status_badge_class($status)
    {
        return AppHelper::getStatusBadgeClass($status);
    }
}

if (!function_exists('get_transaction_type_badge_class')) {
    /**
     * Get transaction type badge class
     */
    function get_transaction_type_badge_class($type)
    {
        return AppHelper::getTransactionTypeBadgeClass($type);
    }
}

if (!function_exists('format_date')) {
    /**
     * Format date for display
     */
    function format_date($date, $format = 'M d, Y')
    {
        return AppHelper::formatDate($date, $format);
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Format date and time for display
     */
    function format_datetime($date, $format = 'M d, Y h:i A')
    {
        return AppHelper::formatDateTime($date, $format);
    }
}

if (!function_exists('time_ago')) {
    /**
     * Get time ago
     */
    function time_ago($date)
    {
        return AppHelper::timeAgo($date);
    }
}

if (!function_exists('mask_data')) {
    /**
     * Mask sensitive data
     */
    function mask_data($data, $type = 'email')
    {
        return AppHelper::maskData($data, $type);
    }
}

if (!function_exists('is_valid_email')) {
    /**
     * Validate email format
     */
    function is_valid_email($email)
    {
        return AppHelper::isValidEmail($email);
    }
}

if (!function_exists('is_valid_url')) {
    /**
     * Validate URL format
     */
    function is_valid_url($url)
    {
        return AppHelper::isValidUrl($url);
    }
}

if (!function_exists('get_user_avatar')) {
    /**
     * Get user avatar URL
     */
    function get_user_avatar($user)
    {
        return AppHelper::getUserAvatar($user);
    }
}

if (!function_exists('get_user_display_name')) {
    /**
     * Get user display name
     */
    function get_user_display_name($user)
    {
        return AppHelper::getUserDisplayName($user);
    }
}

if (!function_exists('has_user_profile_image')) {
    /**
     * Check if user has profile image
     */
    function has_user_profile_image($user)
    {
        return AppHelper::hasUserProfileImage($user);
    }
}

if (!function_exists('storage_url')) {
    /**
     * Get storage URL
     */
    function storage_url($path)
    {
        return AppHelper::storageUrl($path);
    }
}

if (!function_exists('storage_exists')) {
    /**
     * Check if file exists in storage
     */
    function storage_exists($path)
    {
        return AppHelper::storageExists($path);
    }
}

if (!function_exists('delete_storage_file')) {
    /**
     * Delete file from storage
     */
    function delete_storage_file($path)
    {
        return AppHelper::deleteStorageFile($path);
    }
}

if (!function_exists('is_kyc_enabled')) {
    /**
     * Check if KYC is enabled
     */
    function is_kyc_enabled()
    {
        return AppHelper::isKycEnabled();
    }
}

if (!function_exists('is_email_verification_enabled')) {
    /**
     * Check if email verification is enabled
     */
    function is_email_verification_enabled()
    {
        return AppHelper::isEmailVerificationEnabled();
    }
}

if (!function_exists('is_maintenance_mode')) {
    /**
     * Check if maintenance mode is enabled
     */
    function is_maintenance_mode()
    {
        return AppHelper::isMaintenanceMode();
    }
}
