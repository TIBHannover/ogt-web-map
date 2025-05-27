<?php

namespace App\Services\Wikidata;

use Illuminate\Support\Facades\Log;

class WikidataFormatter
{
    /**
     * Entity Type Constants
     */
    public const ENTITY_TYPE_PERSON = "Q5"; // Person
    public const ENTITY_TYPE_EVENT = "Q6983405"; // Ereignis
    public const ENTITY_TYPE_ORGANIZATION = "Q43229"; // Organisation
    public const ENTITY_TYPE_STRUCTURE = "Q811979"; // Architektonische Struktur

    /**
     * Common Property Constants
     */
    public const WIKIDATA_INSTANCE_OF = "P31"; // Instanz von
    public const WIKIDATA_COUNTRY = "P17"; // Staat
    public const WIKIDATA_COORDINATES = "P625"; // Koordinaten
    public const WIKIDATA_CAUSE = "P828"; // Ursache
    public const WIKIDATA_DESCRIBED_BY_SOURCE = "P1343"; // Beschrieben durch Quelle
    public const WIKIDATA_OFFICIAL_NAME = "P1448"; // Offizielle Bezeichnung
    public const WIKIDATA_AUTHOR_STRING = "P2093"; // Autor als String

    /**
     * Person Property Constants
     */
    public const WIKIDATA_DATE_OF_BIRTH = "P569"; // Geburtsdatum
    public const WIKIDATA_DATE_OF_DEATH = "P570"; // Sterbedatum
    public const WIKIDATA_OCCUPATION = "P106"; // Tu00e4tigkeit
    public const WIKIDATA_NATIONALITY = "P27"; // Staatsangehu00f6rigkeit

    /**
     * Event Property Constants
     */
    public const WIKIDATA_POINT_IN_TIME = "P585"; // Zeitpunkt
    public const WIKIDATA_START_TIME = "P580"; // Anfangsdatum
    public const WIKIDATA_END_TIME = "P582"; // Enddatum
    public const WIKIDATA_VICTIMS_COUNT = "P1590"; // Anzahl der Opfer
    public const WIKIDATA_SURVIVORS_COUNT = "P1561"; // Anzahl der Überlebenden
    public const WIKIDATA_DEATHS_COUNT = "P1120"; // Anzahl der Todesfälle
    public const WIKIDATA_ARRESTS_COUNT = "P5582"; // Anzahl der Festnahmen

    /**
     * Organization and Structure Property Constants
     */
    public const WIKIDATA_INCEPTION = "P571"; // Gru00fcndungsdatum
    public const WIKIDATA_DISSOLVED = "P576"; // Auflu00f6sungsdatum/Abriss
    public const WIKIDATA_HEADQUARTERS = "P159"; // Hauptsitz
    public const WIKIDATA_ARCHITECT = "P84"; // Architekt
    public const WIKIDATA_HEIGHT = "P2048"; // Hu00f6he

    /**
     * Source/Reference Property Constants
     */
    public const WIKIDATA_TITLE = "P1476"; // Titel
    public const WIKIDATA_STATED_IN = "P248"; // Beschrieben in
    public const WIKIDATA_PAGE = "P304"; // Seite
    public const WIKIDATA_URL = "P854"; // URL
    public const WIKIDATA_RETRIEVED_DATE = "P813"; // Zugriffsdatum
    public const WIKIDATA_ARCHIVE = "P485"; // Archiv
    public const WIKIDATA_INVENTORY_NUMBER = "P217"; // Inventarnummer

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
        $apiUrl = config("wikidata.api_url");
        $parsedUrl = parse_url($apiUrl);
        $domain = $parsedUrl["host"];
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
        // Basis-Struktur gemäß Wikibase-Dokumentation
        $formattedData = [
            "type" => "item",
            "labels" => [
                "de" => [
                    "language" => "de",
                    "value" => $data["title"] ?? "",
                ],
            ],
            "descriptions" => [
                "de" => [
                    "language" => "de",
                    "value" => $data["description"] ?? "",
                ],
            ],
            "aliases" => [],
            "claims" => [],
            "sitelinks" => [],
        ];

        // Instanz von (P31) - Claim erstellen
        $formattedData["claims"][self::WIKIDATA_INSTANCE_OF] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => self::WIKIDATA_INSTANCE_OF,
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => $type,
                            "numeric-id" => intval(substr($type, 1)), // "Q123" -> 123
                        ],
                        "type" => "wikibase-entityid",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ],
        ];

        // Staat (P17) - Deutschland im Nazi-Regime (Q7318)
        /*
        $formattedData["claims"][self::WIKIDATA_COUNTRY] = [
            [
                "mainsnak" => [
                    "snaktype" => "value",
                    "property" => self::WIKIDATA_COUNTRY,
                    "datavalue" => [
                        "value" => [
                            "entity-type" => "item",
                            "id" => "Q7318",
                            "numeric-id" => 7318,
                        ],
                        "type" => "wikibase-entityid",
                    ],
                ],
                "type" => "statement",
                "rank" => "normal",
            ],
        ];
        */

        // Fu00fcge Koordinaten hinzu, wenn vorhanden
        if (!empty($data["coordinates"])) {
            $coordinates = $this->parseCoordinates($data["coordinates"]);
            if ($coordinates !== null) {
                $formattedData["claims"][self::WIKIDATA_COORDINATES] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_COORDINATES,
                            "datavalue" => [
                                "value" => [
                                    "latitude" => $coordinates["lat"],
                                    "longitude" => $coordinates["lng"],
                                    "precision" => 0.0001,
                                    "globe" =>
                                        "http://www.wikidata.org/entity/Q2",
                                ],
                                "type" => "globecoordinate",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Fu00fcge Ursache der Verfolgung hinzu, wenn vorhanden (P1534 - Ursache)
        if (!empty($data["cause_search"])) {
            $formattedData["claims"][self::WIKIDATA_CAUSE] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_CAUSE,
                        "datavalue" => [
                            "value" => $data["cause_search"],
                            "type" => "string",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ],
            ];
        }

        // Typ-spezifische Formatierungen
        switch ($type) {
            case self::ENTITY_TYPE_PERSON: // Person
                $this->formatPersonEntity($formattedData, $data);
                break;
            case self::ENTITY_TYPE_EVENT: // Ereignis
                $this->formatEventEntity($formattedData, $data);
                break;
            case self::ENTITY_TYPE_ORGANIZATION: // Organisation
                $this->formatOrganizationEntity($formattedData, $data);
                break;
            case self::ENTITY_TYPE_STRUCTURE: // Architektonische Struktur
                $this->formatStructureEntity($formattedData, $data);
                break;
        }

        // Quellenangaben hinzufügen, falls vorhanden
        if (!empty($data["sources"])) {
            $this->addSourcesToEntity($formattedData, $data["sources"]);
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
        if (
            is_array($coordinates) &&
            isset($coordinates["lat"]) &&
            isset($coordinates["lng"])
        ) {
            return [
                "lat" => (float) $coordinates["lat"],
                "lng" => (float) $coordinates["lng"],
            ];
        }

        // Wenn JSON-String
        if (is_string($coordinates)) {
            // Versuche als JSON zu parsen
            if (strpos($coordinates, "{") === 0) {
                $parsed = json_decode($coordinates, true);
                if (
                    json_last_error() === JSON_ERROR_NONE &&
                    isset($parsed["lat"]) &&
                    isset($parsed["lng"])
                ) {
                    return [
                        "lat" => (float) $parsed["lat"],
                        "lng" => (float) $parsed["lng"],
                    ];
                }
            }

            // Versuche als Array zu parsen
            if (strpos($coordinates, "[") === 0) {
                $parsed = json_decode($coordinates, true);
                if (
                    json_last_error() === JSON_ERROR_NONE &&
                    isset($parsed[0]) &&
                    isset($parsed[1])
                ) {
                    return [
                        "lat" => (float) $parsed[0],
                        "lng" => (float) $parsed[1],
                    ];
                }
            }

            // Versuche als Komma-getrennte Werte zu parsen
            $parts = explode(",", $coordinates);
            if (count($parts) >= 2) {
                return [
                    "lat" => (float) trim($parts[0]),
                    "lng" => (float) trim($parts[1]),
                ];
            }
        }

        // Fallback: Fehlermeldung loggen und null zuru00fcckgeben
        Log::warning("Ungu00fcltiges Koordinatenformat", [
            "coordinates" => $coordinates,
        ]);
        return null;
    }

    /**
     * Formatiert Personen-Entities (Q5).
     *
     * @param array &$formattedData Referenz auf die formatierte Datenstruktur
     * @param array $data Originaldaten
     */
    protected function formatPersonEntity(
        array &$formattedData,
        array $data
    ): void {
        // Geburtsdatum (P569)
        if (!empty($data["date_of_birth"])) {
            $birthDate = $this->formatDate($data["date_of_birth"]);
            if ($birthDate !== null) {
                $formattedData["claims"][self::WIKIDATA_DATE_OF_BIRTH] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_DATE_OF_BIRTH,
                            "datavalue" => [
                                "value" => $birthDate,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Sterbedatum (P570)
        if (!empty($data["date_of_death"])) {
            $deathDate = $this->formatDate($data["date_of_death"]);
            if ($deathDate !== null) {
                $formattedData["claims"][self::WIKIDATA_DATE_OF_DEATH] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_DATE_OF_DEATH,
                            "datavalue" => [
                                "value" => $deathDate,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Beruf (P106) - Sollte ein Wikidata-Item sein, nicht ein String
        if (!empty($data["occupation"])) {
            // Wir nehmen hier an, dass occupation entweder eine Wikidata-ID ist oder als String behandelt werden sollte
            // Wenn es eine Wikidata-ID ist (beginnt mit Q), dann als Entity behandeln
            if (
                isset($data["occupation"]) &&
                is_string($data["occupation"]) &&
                substr($data["occupation"], 0, 1) === "Q"
            ) {
                $formattedData["claims"][self::WIKIDATA_OCCUPATION] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_OCCUPATION,
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $data["occupation"],
                                    "numeric-id" => intval(
                                        substr($data["occupation"], 1)
                                    ),
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            } else {
                // Als Freitext-Qualifier hinzufu00fcgen (P1343 - beschrieben durch Quelle)
                $formattedData["claims"][self::WIKIDATA_DESCRIBED_BY_SOURCE] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_DESCRIBED_BY_SOURCE,
                            "datavalue" => [
                                "value" =>
                                    "Berufsbezeichnung: " . $data["occupation"],
                                "type" => "string",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Nationalitu00e4t (P27) - Sollte ein Wikidata-Item sein, nicht ein String
        if (!empty($data["nationality"])) {
            // Wir nehmen hier an, dass nationality entweder eine Wikidata-ID ist oder als String behandelt werden sollte
            // Wenn es eine Wikidata-ID ist (beginnt mit Q), dann als Entity behandeln
            if (
                isset($data["nationality"]) &&
                is_string($data["nationality"]) &&
                substr($data["nationality"], 0, 1) === "Q"
            ) {
                $formattedData["claims"]["P27"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P27",
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $data["nationality"],
                                    "numeric-id" => intval(
                                        substr($data["nationality"], 1)
                                    ),
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            } else {
                // Als Freitext-Qualifier hinzufu00fcgen (P1343 - beschrieben durch Quelle)
                $formattedData["claims"]["P1343"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P1343",
                            "datavalue" => [
                                "value" =>
                                    "Nationalitu00e4t: " . $data["nationality"],
                                "type" => "string",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }
    }

    /**
     * Formatiert Ereignis-Entities (Q6983405).
     *
     * @param array &$formattedData Referenz auf die formatierte Datenstruktur
     * @param array $data Originaldaten
     */
    protected function formatEventEntity(
        array &$formattedData,
        array $data
    ): void {
        // Zeitpunkt (P585) oder Anfangs- und Endzeitpunkt (P580, P582)
        if (!empty($data["time_point"])) {
            $timePoint = $this->formatDate($data["time_point"]);
            if ($timePoint !== null) {
                $formattedData["claims"][self::WIKIDATA_POINT_IN_TIME] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => self::WIKIDATA_POINT_IN_TIME,
                            "datavalue" => [
                                "value" => $timePoint,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        } else {
            // Startzeit (P580)
            if (!empty($data["time_period_start"])) {
                $startTime = $this->formatDate($data["time_period_start"]);
                if ($startTime !== null) {
                    $formattedData["claims"][self::WIKIDATA_START_TIME] = [
                        [
                            "mainsnak" => [
                                "snaktype" => "value",
                                "property" => self::WIKIDATA_START_TIME,
                                "datavalue" => [
                                    "value" => $startTime,
                                    "type" => "time",
                                ],
                            ],
                            "type" => "statement",
                            "rank" => "normal",
                        ],
                    ];
                }
            }

            // Endzeit (P582)
            if (!empty($data["time_period_end"])) {
                $endTime = $this->formatDate($data["time_period_end"]);
                if ($endTime !== null) {
                    $formattedData["claims"][self::WIKIDATA_END_TIME] = [
                        [
                            "mainsnak" => [
                                "snaktype" => "value",
                                "property" => self::WIKIDATA_END_TIME,
                                "datavalue" => [
                                    "value" => $endTime,
                                    "type" => "time",
                                ],
                            ],
                            "type" => "statement",
                            "rank" => "normal",
                        ],
                    ];
                }
            }
        }

        // Ursache (P828) - Sollte ein Wikidata-Item sein, nicht ein String
        if (!empty($data["cause"])) {
            // Wir nehmen hier an, dass cause entweder eine Wikidata-ID ist oder als String behandelt werden sollte
            if (
                isset($data["cause"]) &&
                is_string($data["cause"]) &&
                substr($data["cause"], 0, 1) === "Q"
            ) {
                // Als Wikidata-Item hinzufu00fcgen
                $formattedData["claims"]["P828"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P828",
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $data["cause"],
                                    "numeric-id" => intval(
                                        substr($data["cause"], 1)
                                    ),
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            } else {
                // Als String in der Beschreibung hinzufu00fcgen (P2093 - "Autor" als freitext-Feld)
                $formattedData["claims"]["P2093"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P2093",
                            "datavalue" => [
                                "value" => "Ursache: " . $data["cause"],
                                "type" => "string",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Opferzahl (P1355) - Quantity-Typ
        if (!empty($data["victim_count"])) {
            $formattedData["claims"][self::WIKIDATA_VICTIMS_COUNT] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_VICTIMS_COUNT,
                        "datavalue" => [
                            "value" => [
                                "amount" => "+" . (int) $data["victim_count"],
                                "unit" => "1",
                            ],
                            "type" => "quantity",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ],
            ];
        }

        // u00dcberlebende (eigenes Property, z.B. P1234)
        if (!empty($data["survivor_count"])) {
            $formattedData["claims"][self::WIKIDATA_SURVIVORS_COUNT] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_SURVIVORS_COUNT,
                        "datavalue" => [
                            "value" => [
                                "amount" => "+" . (int) $data["survivor_count"],
                                "unit" => "1",
                            ],
                            "type" => "quantity",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ],
            ];
        }

        // Todesfälle (P1120)
        if (!empty($data["death_count"])) {
            $formattedData["claims"][self::WIKIDATA_DEATHS_COUNT] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_DEATHS_COUNT,
                        "datavalue" => [
                            "value" => [
                                "amount" => "+" . (int) $data["death_count"],
                                "unit" => "1",
                            ],
                            "type" => "quantity",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
                ],
            ];
        }

        // Festnahmen (eigenes Property, z.B. P5678)
        if (!empty($data["arrest_count"])) {
            $formattedData["claims"][self::WIKIDATA_ARRESTS_COUNT] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => self::WIKIDATA_ARRESTS_COUNT,
                        "datavalue" => [
                            "value" => [
                                "amount" => "+" . (int) $data["arrest_count"],
                                "unit" => "1",
                            ],
                            "type" => "quantity",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
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
    protected function formatOrganizationEntity(
        array &$formattedData,
        array $data
    ): void {
        // Gru00fcndungsdatum (P571)
        if (!empty($data["inception"])) {
            $inception = $this->formatDate($data["inception"]);
            if ($inception !== null) {
                $formattedData["claims"]["P571"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P571",
                            "datavalue" => [
                                "value" => $inception,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Auflu00f6sungsdatum (P576)
        if (!empty($data["dissolved_date"])) {
            $dissolvedDate = $this->formatDate($data["dissolved_date"]);
            if ($dissolvedDate !== null) {
                $formattedData["claims"]["P576"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P576",
                            "datavalue" => [
                                "value" => $dissolvedDate,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Hauptsitz (P159) - Sollte ein Wikidata-Item sein
        if (!empty($data["headquarters_location"])) {
            if (
                isset($data["headquarters_location"]) &&
                is_string($data["headquarters_location"]) &&
                substr($data["headquarters_location"], 0, 1) === "Q"
            ) {
                // Als Wikidata-Item hinzufu00fcgen
                $formattedData["claims"]["P159"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P159",
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $data["headquarters_location"],
                                    "numeric-id" => intval(
                                        substr(
                                            $data["headquarters_location"],
                                            1
                                        )
                                    ),
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            } else {
                // Wenn es ein String ist, dann als monolingualer Text (Bezeichnung des Standorts)
                $formattedData["claims"]["P1448"] = [
                    // P1448 ist "offizielle Bezeichnung"
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P1448",
                            "datavalue" => [
                                "value" => [
                                    "text" => $data["headquarters_location"],
                                    "language" => "de",
                                ],
                                "type" => "monolingualtext",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }
    }

    /**
     * Formatiert Architektonische-Struktur-Entities (Q811979).
     *
     * @param array &$formattedData Referenz auf die formatierte Datenstruktur
     * @param array $data Originaldaten
     */
    protected function formatStructureEntity(
        array &$formattedData,
        array $data
    ): void {
        // Errichtungsdatum (P571)
        if (!empty($data["inception"])) {
            $inception = $this->formatDate($data["inception"]);
            if ($inception !== null) {
                $formattedData["claims"]["P571"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P571",
                            "datavalue" => [
                                "value" => $inception,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Abriss-/Zerstu00f6rungsdatum (P576)
        if (!empty($data["demolished_date"])) {
            $demolishedDate = $this->formatDate($data["demolished_date"]);
            if ($demolishedDate !== null) {
                $formattedData["claims"]["P576"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P576",
                            "datavalue" => [
                                "value" => $demolishedDate,
                                "type" => "time",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Architekt (P84) - Sollte ein Wikidata-Item sein
        if (!empty($data["architect"])) {
            if (
                isset($data["architect"]) &&
                is_string($data["architect"]) &&
                substr($data["architect"], 0, 1) === "Q"
            ) {
                // Als Wikidata-Item hinzufu00fcgen
                $formattedData["claims"]["P84"] = [
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P84",
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $data["architect"],
                                    "numeric-id" => intval(
                                        substr($data["architect"], 1)
                                    ),
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            } else {
                // Wenn es ein String ist, als Name des Architekten
                $formattedData["claims"]["P2093"] = [
                    // P2093 ist "Autor als Freitext"
                    [
                        "mainsnak" => [
                            "snaktype" => "value",
                            "property" => "P2093",
                            "datavalue" => [
                                "value" => $data["architect"],
                                "type" => "string",
                            ],
                        ],
                        "type" => "statement",
                        "rank" => "normal",
                    ],
                ];
            }
        }

        // Hu00f6he (P2048) - Quantity-Typ
        if (!empty($data["height"])) {
            $formattedData["claims"]["P2048"] = [
                [
                    "mainsnak" => [
                        "snaktype" => "value",
                        "property" => "P2048",
                        "datavalue" => [
                            "value" => [
                                "amount" => "+" . (float) $data["height"],
                                "unit" =>
                                    "http://www.wikidata.org/entity/Q11573", // Meter
                            ],
                            "type" => "quantity",
                        ],
                    ],
                    "type" => "statement",
                    "rank" => "normal",
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
            $year = $dateTime->format("Y");
            $month = $dateTime->format("m");
            $day = $dateTime->format("d");

            return [
                "time" => "+{$year}-{$month}-{$day}T00:00:00Z",
                "timezone" => 0,
                "before" => 0,
                "after" => 0,
                "precision" => 11, // Tag-Genauigkeit
                "calendarmodel" => "http://www.wikidata.org/entity/Q1985727", // Gregorianischer Kalender
            ];
        } catch (\Exception $e) {
            Log::warning("Fehler beim Formatieren des Datums", [
                "date" => $date,
                "error" => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Fu00fcgt Quellenangaben als Referenzen zu den Claims hinzu.
     *
     * @param array &$formattedData Referenz auf die formatierte Datenstruktur
     * @param array $sources Die Quellenangaben
     */
    protected function addSourcesToEntity(
        array &$formattedData,
        array $sources
    ): void {
        if (empty($sources)) {
            return;
        }

        // Erstelle Referenzen fu00fcr alle relevanten Claims
        foreach ($formattedData["claims"] as $propertyId => &$statements) {
            // Ignoriere bestimmte Properties, die keine Quellen benu00f6tigen
            // z.B. P31 (Instanz von) oder andere grundlegende Eigenschaften
            if (in_array($propertyId, ["P31"])) {
                continue;
            }

            foreach ($statements as &$statement) {
                $statement["references"] = [];

                // Jede Quelle als Referenz hinzufu00fcgen
                foreach ($sources as $source) {
                    // Referenz auf Basis des Quellentyps erstellen
                    $reference = $this->createReferenceFromSource($source);
                    if (!empty($reference)) {
                        $statement["references"][] = $reference;
                    }
                }
            }
        }
    }

    /**
     * Erstellt eine Referenz basierend auf dem Quellentyp.
     *
     * @param array $source Die Quellenangabe
     * @return array Die formatierte Referenz oder leeres Array bei Fehler
     */
    protected function createReferenceFromSource(array $source): array
    {
        if (
            empty($source) ||
            !isset($source["type"]) ||
            !isset($source["title"])
        ) {
            return [];
        }

        $reference = [
            "hash" => "reference-" . md5(json_encode($source)),
            "snaks" => [],
        ];

        // Basisdaten fu00fcr jede Referenz: Titel (P1476)
        $reference["snaks"]["P1476"] = [
            [
                "snaktype" => "value",
                "property" => "P1476",
                "datavalue" => [
                    "value" => [
                        "text" => $source["title"],
                        "language" => "de",
                    ],
                    "type" => "monolingualtext",
                ],
            ],
        ];

        // Fu00fcge spezifische Daten je nach Quellentyp hinzu
        switch ($source["type"]) {
            case "book":
                // Fu00fcr Buchquellen
                if (isset($source["id"]) && $source["id"] !== "manual") {
                    // Angabe der Wikidata-ID (P248)
                    $reference["snaks"]["P248"] = [
                        [
                            "snaktype" => "value",
                            "property" => "P248",
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $source["id"],
                                    "numeric-id" => intval(
                                        substr($source["id"], 1)
                                    ),
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                    ];
                }

                // Seitenangabe (P304)
                if (isset($source["pages"])) {
                    $reference["snaks"]["P304"] = [
                        [
                            "snaktype" => "value",
                            "property" => "P304",
                            "datavalue" => [
                                "value" => $source["pages"],
                                "type" => "string",
                            ],
                        ],
                    ];
                }
                break;

            case "url":
                // Fu00fcr URL-Quellen
                if (isset($source["url"])) {
                    // URL der Referenz (P854)
                    $reference["snaks"]["P854"] = [
                        [
                            "snaktype" => "value",
                            "property" => "P854",
                            "datavalue" => [
                                "value" => $source["url"],
                                "type" => "string",
                            ],
                        ],
                    ];
                }

                // Zugriffsdatum (P813)
                if (isset($source["accessDate"])) {
                    $accessDate = $this->formatDate($source["accessDate"]);
                    if ($accessDate !== null) {
                        $reference["snaks"]["P813"] = [
                            [
                                "snaktype" => "value",
                                "property" => "P813",
                                "datavalue" => [
                                    "value" => $accessDate,
                                    "type" => "time",
                                ],
                            ],
                        ];
                    }
                }
                break;

            case "archive":
                // Fu00fcr Archivquellen
                if (isset($source["id"]) && $source["id"] !== "manual") {
                    // Angabe des Archivs (P485)
                    $reference["snaks"]["P485"] = [
                        [
                            "snaktype" => "value",
                            "property" => "P485",
                            "datavalue" => [
                                "value" => [
                                    "entity-type" => "item",
                                    "id" => $source["id"],
                                    "numeric-id" => intval(
                                        substr($source["id"], 1)
                                    ),
                                ],
                                "type" => "wikibase-entityid",
                            ],
                        ],
                    ];
                }

                // Inventarnummer (P217)
                if (isset($source["inventoryNumber"])) {
                    $reference["snaks"]["P217"] = [
                        [
                            "snaktype" => "value",
                            "property" => "P217",
                            "datavalue" => [
                                "value" => $source["inventoryNumber"],
                                "type" => "string",
                            ],
                        ],
                    ];
                }
                break;
        }

        // Beschreibung als Notiz hinzufu00fcgen (P2093)
        if (isset($source["description"])) {
            $reference["snaks"]["P2093"] = [
                [
                    "snaktype" => "value",
                    "property" => "P2093",
                    "datavalue" => [
                        "value" => $source["description"],
                        "type" => "string",
                    ],
                ],
            ];
        }

        return $reference;
    }
}
