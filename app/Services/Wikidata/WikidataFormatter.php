<?php

namespace App\Services\Wikidata;

use Illuminate\Support\Facades\Log;

class WikidataFormatter
{
    /**
     * The base URL for entity references
     */
    protected string $entityUrlBase;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Extract domain from API URL to create entity URL base
        $apiUrl = config('wikidata.api_url');
        $parsedUrl = parse_url($apiUrl);
        $domain = $parsedUrl['host'];
        $this->entityUrlBase = "https://{$domain}/entity/";
    }
    /**
     * Formatiert die Daten fu00fcr Wikidata basierend auf dem Entity-Typ.
     *
     * @param array $data Die Ausgangsdaten aus dem Request
     * @param string $type Der Entity-Typ (Q5 = Person, Q6983405 = Ereignis, Q43229 = Organisation, Q811979 = Architektonische Struktur)
     * @return array Formatierte Daten fu00fcr Wikidata
     */
    public function formatEntityData(array $data, string $type): array
    {
        // Basis-Struktur fu00fcr alle Entity-Typen
        $formattedData = [
            'labels' => [
                'de' => [
                    'language' => 'de',
                    'value' => $data['title'] ?? '',
                ],
            ],
            'descriptions' => [
                'de' => [
                    'language' => 'de',
                    'value' => $data['description'] ?? '',
                ],
            ],
            'claims' => [
                // Instanz von (P31)
                'P31' => [
                    [
                        'mainsnak' => [
                            'snaktype' => 'value',
                            'property' => 'P31',
                            'datavalue' => [
                                'value' => $this->entityUrlBase . $type,
                                'type' => 'string',
                            ],
                        ],
                        'type' => 'statement',
                        'rank' => 'normal',
                    ],
                ],
            ],
        ];

        // Staat (P17) - Deutschland im Nazi-Regime (Q7318)
        $formattedData['claims']['P17'] = [
            [
                'mainsnak' => [
                    'snaktype' => 'value',
                    'property' => 'P17',
                    'datavalue' => [
                        'value' => [
                            'entity-type' => 'item',
                            'id' => 'Q7318',
                            'numeric-id' => 7318,
                        ],
                        'type' => 'wikibase-entityid',
                    ],
                ],
                'type' => 'statement',
                'rank' => 'normal',
            ],
        ];
        
        // Fu00fcge Koordinaten hinzu, wenn vorhanden
        if (!empty($data['coordinates'])) {
            $coordinates = $this->parseCoordinates($data['coordinates']);
            if ($coordinates !== null) {
                $formattedData['claims']['P625'] = [
                    [
                        'mainsnak' => [
                            'snaktype' => 'value',
                            'property' => 'P625',
                            'datavalue' => [
                                'value' => [
                                    'latitude' => $coordinates['lat'],
                                    'longitude' => $coordinates['lng'],
                                    'precision' => 0.0001,
                                    'globe' => 'http://www.wikidata.org/entity/Q2',
                                ],
                                'type' => 'globecoordinate',
                            ],
                        ],
                        'type' => 'statement',
                        'rank' => 'normal',
                    ],
                ];
            }
        }
        
        // Fu00fcge Ursache der Verfolgung hinzu, wenn vorhanden (P1534 - Ursache)
        if (!empty($data['cause_search'])) {
            $formattedData['claims']['P1534'] = [
                [
                    'mainsnak' => [
                        'snaktype' => 'value',
                        'property' => 'P1534',
                        'datavalue' => [
                            'value' => $data['cause_search'],
                            'type' => 'string',
                        ],
                    ],
                    'type' => 'statement',
                    'rank' => 'normal',
                ],
            ];
        }

        // Typ-spezifische Formatierungen
        switch ($type) {
            case 'Q5': // Person
                $this->formatPersonEntity($formattedData, $data);
                break;
            case 'Q6983405': // Ereignis
                $this->formatEventEntity($formattedData, $data);
                break;
            case 'Q43229': // Organisation
                $this->formatOrganizationEntity($formattedData, $data);
                break;
            case 'Q811979': // Architektonische Struktur
                $this->formatStructureEntity($formattedData, $data);
                break;
        }

        return $formattedData;
    }

    /**
     * Konvertiert verschiedene Koordinatenformate in ein einheitliches Format.
     * 
     * @param mixed $coordinates Koordinaten in verschiedenen Formaten
     * @return array|null Array mit 'lat' und 'lng' oder null bei ungu00fcltigem Format
     */
    protected function parseCoordinates($coordinates)
    {
        if (empty($coordinates)) {
            return null;
        }

        // Wenn bereits ein Array im korrekten Format
        if (is_array($coordinates) && isset($coordinates['lat']) && isset($coordinates['lng'])) {
            return [
                'lat' => (float) $coordinates['lat'],
                'lng' => (float) $coordinates['lng'],
            ];
        }

        // Wenn JSON-String
        if (is_string($coordinates)) {
            // Versuche als JSON zu parsen
            if (strpos($coordinates, '{') === 0) {
                $parsed = json_decode($coordinates, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($parsed['lat']) && isset($parsed['lng'])) {
                    return [
                        'lat' => (float) $parsed['lat'],
                        'lng' => (float) $parsed['lng'],
                    ];
                }
            }

            // Versuche als Array zu parsen
            if (strpos($coordinates, '[') === 0) {
                $parsed = json_decode($coordinates, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($parsed[0]) && isset($parsed[1])) {
                    return [
                        'lat' => (float) $parsed[0],
                        'lng' => (float) $parsed[1],
                    ];
                }
            }

            // Versuche als Komma-getrennte Werte zu parsen
            $parts = explode(',', $coordinates);
            if (count($parts) >= 2) {
                return [
                    'lat' => (float) trim($parts[0]),
                    'lng' => (float) trim($parts[1]),
                ];
            }
        }

        // Fallback: Fehlermeldung loggen und null zuru00fcckgeben
        Log::warning('Ungu00fcltiges Koordinatenformat', ['coordinates' => $coordinates]);
        return null;
    }

    /**
     * Formatiert Personen-Entities (Q5).
     * 
     * @param array &$formattedData Referenz auf die formatierte Datenstruktur
     * @param array $data Originaldaten
     */
    protected function formatPersonEntity(array &$formattedData, array $data): void
    {
        // Geburtsdatum (P569)
        if (!empty($data['date_of_birth'])) {
            $birthDate = $this->formatDate($data['date_of_birth']);
            if ($birthDate !== null) {
                $formattedData['claims']['P569'] = [
                    [
                        'mainsnak' => [
                            'snaktype' => 'value',
                            'property' => 'P569',
                            'datavalue' => [
                                'value' => $birthDate,
                                'type' => 'time',
                            ],
                        ],
                        'type' => 'statement',
                        'rank' => 'normal',
                    ],
                ];
            }
        }

        // Sterbedatum (P570)
        if (!empty($data['date_of_death'])) {
            $deathDate = $this->formatDate($data['date_of_death']);
            if ($deathDate !== null) {
                $formattedData['claims']['P570'] = [
                    [
                        'mainsnak' => [
                            'snaktype' => 'value',
                            'property' => 'P570',
                            'datavalue' => [
                                'value' => $deathDate,
                                'type' => 'time',
                            ],
                        ],
                        'type' => 'statement',
                        'rank' => 'normal',
                    ],
                ];
            }
        }

        // Beruf (P106)
        if (!empty($data['occupation'])) {
            $formattedData['claims']['P106'] = [
                [
                    'mainsnak' => [
                        'snaktype' => 'value',
                        'property' => 'P106',
                        'datavalue' => [
                            'value' => $data['occupation'],
                            'type' => 'string',
                        ],
                    ],
                    'type' => 'statement',
                    'rank' => 'normal',
                ],
            ];
        }

        // Nationalitu00e4t (P27)
        if (!empty($data['nationality'])) {
            $formattedData['claims']['P27'] = [
                [
                    'mainsnak' => [
                        'snaktype' => 'value',
                        'property' => 'P27',
                        'datavalue' => [
                            'value' => $data['nationality'],
                            'type' => 'string',
                        ],
                    ],
                    'type' => 'statement',
                    'rank' => 'normal',
                ],
            ];
        }
    }

    /**
     * Formatiert Ereignis-Entities (Q6983405).
     * 
     * @param array &$formattedData Referenz auf die formatierte Datenstruktur
     * @param array $data Originaldaten
     */
    protected function formatEventEntity(array &$formattedData, array $data): void
    {
        // Zeitpunkt (P585) oder Anfangs- und Endzeitpunkt (P580, P582)
        if (!empty($data['time_point'])) {
            $timePoint = $this->formatDate($data['time_point']);
            if ($timePoint !== null) {
                $formattedData['claims']['P585'] = [
                    [
                        'mainsnak' => [
                            'snaktype' => 'value',
                            'property' => 'P585',
                            'datavalue' => [
                                'value' => $timePoint,
                                'type' => 'time',
                            ],
                        ],
                        'type' => 'statement',
                        'rank' => 'normal',
                    ],
                ];
            }
        } else {
            // Startzeit (P580)
            if (!empty($data['time_period_start'])) {
                $startTime = $this->formatDate($data['time_period_start']);
                if ($startTime !== null) {
                    $formattedData['claims']['P580'] = [
                        [
                            'mainsnak' => [
                                'snaktype' => 'value',
                                'property' => 'P580',
                                'datavalue' => [
                                    'value' => $startTime,
                                    'type' => 'time',
                                ],
                            ],
                            'type' => 'statement',
                            'rank' => 'normal',
                        ],
                    ];
                }
            }

            // Endzeit (P582)
            if (!empty($data['time_period_end'])) {
                $endTime = $this->formatDate($data['time_period_end']);
                if ($endTime !== null) {
                    $formattedData['claims']['P582'] = [
                        [
                            'mainsnak' => [
                                'snaktype' => 'value',
                                'property' => 'P582',
                                'datavalue' => [
                                    'value' => $endTime,
                                    'type' => 'time',
                                ],
                            ],
                            'type' => 'statement',
                            'rank' => 'normal',
                        ],
                    ];
                }
            }
        }

        // Ursache (P828)
        if (!empty($data['cause'])) {
            $formattedData['claims']['P828'] = [
                [
                    'mainsnak' => [
                        'snaktype' => 'value',
                        'property' => 'P828',
                        'datavalue' => [
                            'value' => $data['cause'],
                            'type' => 'string',
                        ],
                    ],
                    'type' => 'statement',
                    'rank' => 'normal',
                ],
            ];
        }

        // Opferzahl (P1355)
        if (!empty($data['victim_count'])) {
            $formattedData['claims']['P1355'] = [
                [
                    'mainsnak' => [
                        'snaktype' => 'value',
                        'property' => 'P1355',
                        'datavalue' => [
                            'value' => [
                                'amount' => '+' . (int)$data['victim_count'],
                                'unit' => '1',
                            ],
                            'type' => 'quantity',
                        ],
                    ],
                    'type' => 'statement',
                    'rank' => 'normal',
                ],
            ];
        }
    }

    /**
     * Formatiert Organisations-Entities (Q43229).
     * 
     * @param array &$formattedData Referenz auf die formatierte Datenstruktur
     * @param array $data Originaldaten
     */
    protected function formatOrganizationEntity(array &$formattedData, array $data): void
    {
        // Gru00fcndungsdatum (P571)
        if (!empty($data['inception'])) {
            $inception = $this->formatDate($data['inception']);
            if ($inception !== null) {
                $formattedData['claims']['P571'] = [
                    [
                        'mainsnak' => [
                            'snaktype' => 'value',
                            'property' => 'P571',
                            'datavalue' => [
                                'value' => $inception,
                                'type' => 'time',
                            ],
                        ],
                        'type' => 'statement',
                        'rank' => 'normal',
                    ],
                ];
            }
        }

        // Auflu00f6sungsdatum (P576)
        if (!empty($data['dissolved_date'])) {
            $dissolvedDate = $this->formatDate($data['dissolved_date']);
            if ($dissolvedDate !== null) {
                $formattedData['claims']['P576'] = [
                    [
                        'mainsnak' => [
                            'snaktype' => 'value',
                            'property' => 'P576',
                            'datavalue' => [
                                'value' => $dissolvedDate,
                                'type' => 'time',
                            ],
                        ],
                        'type' => 'statement',
                        'rank' => 'normal',
                    ],
                ];
            }
        }

        // Hauptsitz (P159)
        if (!empty($data['headquarters_location'])) {
            $formattedData['claims']['P159'] = [
                [
                    'mainsnak' => [
                        'snaktype' => 'value',
                        'property' => 'P159',
                        'datavalue' => [
                            'value' => $data['headquarters_location'],
                            'type' => 'string',
                        ],
                    ],
                    'type' => 'statement',
                    'rank' => 'normal',
                ],
            ];
        }
    }

    /**
     * Formatiert Architektonische-Struktur-Entities (Q811979).
     * 
     * @param array &$formattedData Referenz auf die formatierte Datenstruktur
     * @param array $data Originaldaten
     */
    protected function formatStructureEntity(array &$formattedData, array $data): void
    {
        // Errichtungsdatum (P571)
        if (!empty($data['inception'])) {
            $inception = $this->formatDate($data['inception']);
            if ($inception !== null) {
                $formattedData['claims']['P571'] = [
                    [
                        'mainsnak' => [
                            'snaktype' => 'value',
                            'property' => 'P571',
                            'datavalue' => [
                                'value' => $inception,
                                'type' => 'time',
                            ],
                        ],
                        'type' => 'statement',
                        'rank' => 'normal',
                    ],
                ];
            }
        }

        // Abriss-/Zerstu00f6rungsdatum (P576)
        if (!empty($data['demolished_date'])) {
            $demolishedDate = $this->formatDate($data['demolished_date']);
            if ($demolishedDate !== null) {
                $formattedData['claims']['P576'] = [
                    [
                        'mainsnak' => [
                            'snaktype' => 'value',
                            'property' => 'P576',
                            'datavalue' => [
                                'value' => $demolishedDate,
                                'type' => 'time',
                            ],
                        ],
                        'type' => 'statement',
                        'rank' => 'normal',
                    ],
                ];
            }
        }

        // Architekt (P84)
        if (!empty($data['architect'])) {
            $formattedData['claims']['P84'] = [
                [
                    'mainsnak' => [
                        'snaktype' => 'value',
                        'property' => 'P84',
                        'datavalue' => [
                            'value' => $data['architect'],
                            'type' => 'string',
                        ],
                    ],
                    'type' => 'statement',
                    'rank' => 'normal',
                ],
            ];
        }

        // Hu00f6he (P2048)
        if (!empty($data['height'])) {
            $formattedData['claims']['P2048'] = [
                [
                    'mainsnak' => [
                        'snaktype' => 'value',
                        'property' => 'P2048',
                        'datavalue' => [
                            'value' => [
                                'amount' => '+' . (float)$data['height'],
                                'unit' => 'http://www.wikidata.org/entity/Q11573', // Meter
                            ],
                            'type' => 'quantity',
                        ],
                    ],
                    'type' => 'statement',
                    'rank' => 'normal',
                ],
            ];
        }
    }

    /**
     * Formatiert ein Datum in das von Wikidata erwartete Format.
     * 
     * @param string $date Das zu formatierende Datum
     * @return array|null Ein formatiertes Datum fu00fcr Wikidata oder null bei Fehler
     */
    protected function formatDate(string $date): ?array
    {
        try {
            $dateTime = new \DateTime($date);
            $year = $dateTime->format('Y');
            $month = $dateTime->format('m');
            $day = $dateTime->format('d');
            
            return [
                'time' => "+{$year}-{$month}-{$day}T00:00:00Z",
                'timezone' => 0,
                'before' => 0,
                'after' => 0,
                'precision' => 11, // Tag-Genauigkeit
                'calendarmodel' => 'http://www.wikidata.org/entity/Q1985727', // Gregorianischer Kalender
            ];
        } catch (\Exception $e) {
            Log::warning('Fehler beim Formatieren des Datums', ['date' => $date, 'error' => $e->getMessage()]);
            return null;
        }
    }
}