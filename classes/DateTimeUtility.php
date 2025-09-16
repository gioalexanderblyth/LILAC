<?php

/**
 * DateTimeUtility Class
 * 
 * Centralized date and time handling utility for the LILAC project.
 * Provides consistent formatting, parsing, and manipulation across all files.
 */
class DateTimeUtility
{
    /**
     * Format a date string to a consistent format
     * 
     * @param string $dateString The date string to format
     * @param string $format The desired output format (default: 'Y-m-d')
     * @return string Formatted date string
     */
    public static function formatDate($dateString, $format = 'Y-m-d')
    {
        try {
            $date = new DateTime($dateString);
            return $date->format($format);
        } catch (Exception $e) {
            error_log("DateTimeUtility::formatDate error: " . $e->getMessage());
            return $dateString; // Return original if parsing fails
        }
    }

    /**
     * Format a time string to a consistent format
     * 
     * @param string $timeString The time string to format
     * @param string $format The desired output format (default: 'H:i')
     * @return string Formatted time string
     */
    public static function formatTime($timeString, $format = 'H:i')
    {
        try {
            $time = new DateTime($timeString);
            return $time->format($format);
        } catch (Exception $e) {
            error_log("DateTimeUtility::formatTime error: " . $e->getMessage());
            return $timeString; // Return original if parsing fails
        }
    }

    /**
     * Format a datetime string to a consistent format
     * 
     * @param string $datetimeString The datetime string to format
     * @param string $format The desired output format (default: 'Y-m-d H:i')
     * @return string Formatted datetime string
     */
    public static function formatDateTime($datetimeString, $format = 'Y-m-d H:i')
    {
        try {
            $datetime = new DateTime($datetimeString);
            return $datetime->format($format);
        } catch (Exception $e) {
            error_log("DateTimeUtility::formatDateTime error: " . $e->getMessage());
            return $datetimeString; // Return original if parsing fails
        }
    }

    /**
     * Get a human-readable relative time (e.g., "2 hours ago", "in 3 days")
     * 
     * @param string $datetimeString The datetime string
     * @return string Human-readable relative time
     */
    public static function getRelativeTime($datetimeString)
    {
        try {
            $datetime = new DateTime($datetimeString);
            $now = new DateTime();
            $diff = $now->diff($datetime);
            
            if ($diff->invert) {
                // Past time
                if ($diff->days > 0) {
                    return $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
                } elseif ($diff->h > 0) {
                    return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
                } elseif ($diff->i > 0) {
                    return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
                } else {
                    return 'Just now';
                }
            } else {
                // Future time
                if ($diff->days > 0) {
                    return 'in ' . $diff->days . ' day' . ($diff->days > 1 ? 's' : '');
                } elseif ($diff->h > 0) {
                    return 'in ' . $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
                } elseif ($diff->i > 0) {
                    return 'in ' . $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
                } else {
                    return 'Now';
                }
            }
        } catch (Exception $e) {
            error_log("DateTimeUtility::getRelativeTime error: " . $e->getMessage());
            return $datetimeString; // Return original if parsing fails
        }
    }

    /**
     * Check if a date is today
     * 
     * @param string $dateString The date string to check
     * @return bool True if the date is today
     */
    public static function isToday($dateString)
    {
        try {
            $date = new DateTime($dateString);
            $today = new DateTime();
            return $date->format('Y-m-d') === $today->format('Y-m-d');
        } catch (Exception $e) {
            error_log("DateTimeUtility::isToday error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a date is in the past
     * 
     * @param string $dateString The date string to check
     * @return bool True if the date is in the past
     */
    public static function isPast($dateString)
    {
        try {
            $date = new DateTime($dateString);
            $now = new DateTime();
            return $date < $now;
        } catch (Exception $e) {
            error_log("DateTimeUtility::isPast error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a date is in the future
     * 
     * @param string $dateString The date string to check
     * @return bool True if the date is in the future
     */
    public static function isFuture($dateString)
    {
        try {
            $date = new DateTime($dateString);
            $now = new DateTime();
            return $date > $now;
        } catch (Exception $e) {
            error_log("DateTimeUtility::isFuture error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the start of the day for a given date
     * 
     * @param string $dateString The date string
     * @return string Start of day datetime string
     */
    public static function getStartOfDay($dateString)
    {
        try {
            $date = new DateTime($dateString);
            $date->setTime(0, 0, 0);
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            error_log("DateTimeUtility::getStartOfDay error: " . $e->getMessage());
            return $dateString;
        }
    }

    /**
     * Get the end of the day for a given date
     * 
     * @param string $dateString The date string
     * @return string End of day datetime string
     */
    public static function getEndOfDay($dateString)
    {
        try {
            $date = new DateTime($dateString);
            $date->setTime(23, 59, 59);
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            error_log("DateTimeUtility::getEndOfDay error: " . $e->getMessage());
            return $dateString;
        }
    }

    /**
     * Add days to a date
     * 
     * @param string $dateString The date string
     * @param int $days Number of days to add (can be negative)
     * @return string New date string
     */
    public static function addDays($dateString, $days)
    {
        try {
            $date = new DateTime($dateString);
            $date->add(new DateInterval('P' . abs($days) . 'D'));
            if ($days < 0) {
                $date->sub(new DateInterval('P' . abs($days) . 'D'));
            }
            return $date->format('Y-m-d');
        } catch (Exception $e) {
            error_log("DateTimeUtility::addDays error: " . $e->getMessage());
            return $dateString;
        }
    }

    /**
     * Get the current date in a consistent format
     * 
     * @param string $format The desired format (default: 'Y-m-d')
     * @return string Current date string
     */
    public static function getCurrentDate($format = 'Y-m-d')
    {
        return (new DateTime())->format($format);
    }

    /**
     * Get the current datetime in a consistent format
     * 
     * @param string $format The desired format (default: 'Y-m-d H:i:s')
     * @return string Current datetime string
     */
    public static function getCurrentDateTime($format = 'Y-m-d H:i:s')
    {
        return (new DateTime())->format($format);
    }
}
