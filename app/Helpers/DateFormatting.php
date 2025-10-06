<?php

use Carbon\Carbon;

// Define the helper function if it doesn't exist yet
if (!function_exists("formatDateBasedOnPrecision")) {
    /**
     * Format a date string based on its precision (year, month, or full date)
     *
     * @param string|null $dateString The date string to format
     * @return string The formatted date string
     */
    function formatDateBasedOnPrecision($dateString)
    {
        if (!$dateString) {
            return "";
        }

        // Check format to determine precision
        if (preg_match('/^\d{4}$/', $dateString)) {
            // Year only
            return $dateString;
        } elseif (preg_match('/^\d{4}-\d{2}$/', $dateString)) {
            // Year and month
            $date = Carbon::parse($dateString);
            $monthNames = [
                "01" => "Januar",
                "02" => "Februar",
                "03" => "März",
                "04" => "April",
                "05" => "Mai",
                "06" => "Juni",
                "07" => "Juli",
                "08" => "August",
                "09" => "September",
                "10" => "Oktober",
                "11" => "November",
                "12" => "Dezember",
            ];
            $month = $date->format("m");
            $year = $date->format("Y");
            return $monthNames[$month] . " " . $year;
        } else {
            // Full date
            return Carbon::parse($dateString)->format("d.m.Y");
        }
    }
}
