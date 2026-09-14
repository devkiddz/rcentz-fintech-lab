<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AppHelper
{
    /**
     * Get a setting value by key
     */
    public static function setting($key, $default = null)
    {
        return Setting::get($key, $default);
    }

    /**
     * Check if a setting is enabled
     */
    public static function isSettingEnabled($key)
    {
        return Setting::isEnabled($key);
    }

    /**
     * Get site name
     */
    public static function siteName()
    {
        return self::setting('site_name', config('app.name', 'Tesla Investment Platform'));
    }

    /**
     * Get site URL
     */
    public static function siteUrl()
    {
        return self::setting('site_url', config('app.url', '/'));
    }

    /**
     * Get site email
     */
    public static function siteEmail()
    {
        return self::setting('site_email', config('mail.from.address', 'admin@example.com'));
    }

    /**
     * Get site phone
     */
    public static function sitePhone()
    {
        return self::setting('site_phone', '');
    }

    /**
     * Get site logo URL
     */
    public static function siteLogo()
    {
        return self::siteLogoLight();
    }

    public static function siteLogoLight()
    {
        $logo = self::setting('site_logo_light', self::setting('site_logo'));
        return $logo ? asset('storage/' . $logo) : null;
    }

    public static function siteLogoDark()
    {
        $logo = self::setting('site_logo_dark');
        return $logo ? asset('storage/' . $logo) : self::siteLogoLight();
    }

    /**
     * Get site favicon URL
     */
    public static function siteFavicon()
    {
        $favicon = self::setting('site_favicon');
        return $favicon ? asset('storage/' . $favicon) : null;
    }

    /**
     * Check if KYC is enabled
     */
    public static function isKycEnabled()
    {
        return self::isSettingEnabled('enable_kyc');
    }

    /**
     * Check if email verification is enabled
     */
    public static function isEmailVerificationEnabled()
    {
        return self::isSettingEnabled('enable_email_verification');
    }

    /**
     * Check if maintenance mode is enabled
     */
    public static function isMaintenanceMode()
    {
        return self::isSettingEnabled('maintenance_mode');
    }

    /**
     * Get currency symbol
     */
    public static function getCurrencySymbol($currency = null, $user = null)
    {
        if (!$currency) {
            // Try to get from provided user first
            if ($user && isset($user->currency)) {
                $currency = $user->currency;
            }
            // Fallback to authenticated user
            elseif (auth()->check()) {
                $currency = auth()->user()->currency ?? 'USD';
            }
        }
        
        $currency = $currency ?? 'USD';
        
        $currencyService = new \App\Services\CurrencyService();
        return $currencyService->getSymbol($currency);
    }

    /**
     * Format currency with conversion
     * Converts amount from USD to user's currency
     */
    public static function formatCurrency($amount, $fromCurrency = 'USD', $toCurrency = null, $user = null)
    {
        if (!$toCurrency) {
            // Try to get from provided user first
            if ($user && isset($user->currency)) {
                $toCurrency = $user->currency;
            }
            // Fallback to authenticated user
            elseif (auth()->check()) {
                $toCurrency = auth()->user()->currency ?? 'USD';
            }
        }
        
        $toCurrency = $toCurrency ?? 'USD';
        
        $currencyService = new \App\Services\CurrencyService();
        
        // Convert the amount
        $convertedAmount = $currencyService->convert($amount, $fromCurrency, $toCurrency);
        
        // Format with symbol
        return $currencyService->format($convertedAmount, $toCurrency);
    }

    /**
     * Format percentage
     */
    public static function formatPercentage($value, $decimals = 2)
    {
        return number_format($value, $decimals) . '%';
    }

    /**
     * Format number with abbreviation (K, M, B)
     */
    public static function formatNumber($number)
    {
        if ($number >= 1000000000) {
            return number_format($number / 1000000000, 1) . 'B';
        } elseif ($number >= 1000000) {
            return number_format($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return number_format($number / 1000, 1) . 'K';
        }
        return number_format($number);
    }

    /**
     * Get initials from name
     */
    public static function getInitials($name)
    {
        $words = explode(' ', trim($name));
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        
        return substr($initials, 0, 2);
    }

    /**
     * Generate a random string
     */
    public static function randomString($length = 10)
    {
        return Str::random($length);
    }

    /**
     * Generate a unique reference ID
     */
    public static function generateReferenceId($prefix = 'REF')
    {
        return $prefix . '_' . strtoupper(Str::random(8)) . '_' . time();
    }

    /**
     * Get file extension from URL
     */
    public static function getFileExtension($url)
    {
        return pathinfo($url, PATHINFO_EXTENSION);
    }

    /**
     * Check if file is an image
     */
    public static function isImage($filename)
    {
        $extension = strtolower(self::getFileExtension($filename));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'ico']);
    }

    /**
     * Get file size in human readable format
     */
    public static function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Get status badge class
     */
    public static function getStatusBadgeClass($status)
    {
        switch (strtolower($status)) {
            case 'completed':
            case 'approved':
            case 'active':
                return 'bg-green-100 text-green-800';
            case 'pending':
                return 'bg-yellow-100 text-yellow-800';
            case 'rejected':
            case 'cancelled':
            case 'inactive':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    /**
     * Get transaction type badge class
     */
    public static function getTransactionTypeBadgeClass($type)
    {
        switch (strtolower($type)) {
            case 'deposit':
                return 'bg-green-100 text-green-800';
            case 'withdrawal':
                return 'bg-red-100 text-red-800';
            case 'investment':
                return 'bg-blue-100 text-blue-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    /**
     * Format date for display
     */
    public static function formatDate($date, $format = 'M d, Y')
    {
        if (!$date) return 'N/A';
        
        if (is_string($date)) {
            $date = new \DateTime($date);
        }
        
        return $date->format($format);
    }

    /**
     * Format date and time for display
     */
    public static function formatDateTime($date, $format = 'M d, Y h:i A')
    {
        if (!$date) return 'N/A';
        
        if (is_string($date)) {
            $date = new \DateTime($date);
        }
        
        return $date->format($format);
    }

    /**
     * Get time ago
     */
    public static function timeAgo($date)
    {
        if (!$date) return 'N/A';
        
        $now = new \DateTime();
        $ago = new \DateTime($date);
        $diff = $now->diff($ago);
        
        if ($diff->y > 0) {
            return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        } elseif ($diff->m > 0) {
            return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        } elseif ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'Just now';
        }
    }

    /**
     * Mask sensitive data
     */
    public static function maskData($data, $type = 'email')
    {
        switch ($type) {
            case 'email':
                $parts = explode('@', $data);
                if (count($parts) === 2) {
                    $username = $parts[0];
                    $domain = $parts[1];
                    $maskedUsername = substr($username, 0, 2) . str_repeat('*', strlen($username) - 2);
                    return $maskedUsername . '@' . $domain;
                }
                return $data;
            
            case 'phone':
                return substr($data, 0, 4) . str_repeat('*', strlen($data) - 8) . substr($data, -4);
            
            case 'card':
                return '**** **** **** ' . substr($data, -4);
            
            default:
                return $data;
        }
    }

    /**
     * Validate email format
     */
    public static function isValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL format
     */
    public static function isValidUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Clean phone number
     */
    public static function cleanPhoneNumber($phone)
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }

    /**
     * Get user avatar URL or initials
     */
    public static function getUserAvatar($user)
    {
        if ($user->profile_image) {
            return asset('storage/' . $user->profile_image);
        }
        return null;
    }

    /**
     * Get user display name
     */
    public static function getUserDisplayName($user)
    {
        return $user->name ?? $user->email;
    }

    /**
     * Check if user has profile image
     */
    public static function hasUserProfileImage($user)
    {
        return !empty($user->profile_image);
    }

    /**
     * Get pagination info
     */
    public static function getPaginationInfo($paginator)
    {
        $total = $paginator->total();
        $perPage = $paginator->perPage();
        $currentPage = $paginator->currentPage();
        $from = ($currentPage - 1) * $perPage + 1;
        $to = min($currentPage * $perPage, $total);
        
        return [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'from' => $from,
            'to' => $to,
            'showing' => "Showing {$from} to {$to} of {$total} results"
        ];
    }

    /**
     * Get storage URL
     */
    public static function storageUrl($path)
    {
        if (empty($path)) return null;
        return asset('storage/' . $path);
    }

    /**
     * Check if file exists in storage
     */
    public static function storageExists($path)
    {
        if (empty($path)) return false;
        return Storage::disk('public')->exists($path);
    }

    /**
     * Delete file from storage
     */
    public static function deleteStorageFile($path)
    {
        if (!empty($path) && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }
        return false;
    }
}
