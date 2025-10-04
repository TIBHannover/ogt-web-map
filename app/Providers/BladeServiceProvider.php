<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Format date based on precision (year, month, or full date)
        Blade::directive("formatDate", function ($expression) {
            return "<?php echo formatDateBasedOnPrecision($expression); ?>";
        });

        // Define a helper function in the global namespace for use in Blade templates
        if (!function_exists("formatDateBasedOnPrecision")) {
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
                    $date = \Carbon\Carbon::parse($dateString);
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
                    return \Carbon\Carbon::parse($dateString)->format("d.m.Y");
                }
            }
        }
    }
}
