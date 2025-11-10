@php
/**
 * This file contains helper functions for Wikidata integration
 * Include it with @include('admin.partials.wikidata-helpers') in other blade files
 */

// Only define functions if they don't already exist to avoid redeclaration errors
if (!function_exists('formatEntityLink')) {
    /**
     * Format a Wikidata entity as a link
     *
     * @param string $id The entity ID (e.g. Q12345)
     * @param string|null $label Optional label to display
     * @return string HTML link to the entity
     */
    function formatEntityLink($id, $label = null) {
        return '<a href="https://www.wikidata.org/wiki/' . htmlspecialchars($id) . '" target="_blank" rel="noopener noreferrer">'
            . htmlspecialchars($label ?: $id)
            . ' <small>(' . htmlspecialchars($id) . ')</small></a>';
    }
}

if (!function_exists('safeFormatWikidata')) {
    /**
     * Safely format a Wikidata entity with error handling
     *
     * @param string $id The entity ID
     * @return string Formatted HTML
     */
    function safeFormatWikidata($id) {
        if (empty($id)) {
            return '';
        }

        try {
            // If it's a Wikidata ID (starts with Q or P)
            if (is_string($id) && preg_match('/^[QP]\d+$/', $id)) {
                $label = $id; // Default to ID if no label found

                // Try to get label from Wikidata
                try {
                    $entityData = \App\Http\Controllers\WikidataPropertyController::getEntityData($id);
                    if ($entityData && isset($entityData['label'])) {
                        $label = $entityData['label'];
                    }
                } catch (\Exception $e) {
                    // Just use the ID if there's an error
                }

                return formatEntityLink($id, $label);
            } else {
                // If not a Wikidata ID, just return as is
                return htmlspecialchars($id);
            }
        } catch (\Exception $e) {
            // Fallback for any errors
            return htmlspecialchars($id);
        }
    }
}

if (!function_exists('formatDateWithQualifier')) {
    /**
     * Format a date with an optional qualifier
     *
     * @param string|null $date The date to format
     * @param string|null $qualifier Optional qualifier for the date
     * @return string Formatted date with qualifier
     */
    function formatDateWithQualifier($date, $qualifier = null) {
        if (empty($date)) {
            return '';
        }

        $formattedDate = formatDateBasedOnPrecision($date);

        if (!empty($qualifier)) {
            $formattedDate .= ' <span class="text-muted">(' . htmlspecialchars($qualifier) . ')</span>';
        }

        return $formattedDate;
    }
}

if (!function_exists('formatNumberWithQualifier')) {
    /**
     * Format a number with an optional qualifier
     *
     * @param string|null $number The number to format
     * @param string|null $qualifier Optional qualifier for the number
     * @return string Formatted number with qualifier
     */
    function formatNumberWithQualifier($number, $qualifier = null) {
        if (empty($number)) {
            return '';
        }

        $formattedNumber = number_format((float)$number, 0, ',', '.');

        if (!empty($qualifier)) {
            $formattedNumber .= ' <span class="text-muted">(' . htmlspecialchars($qualifier) . ')</span>';
        }

        return $formattedNumber;
    }
}
@endphp
