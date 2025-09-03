<?php

namespace RRZE\FAUdir;

defined('ABSPATH') || exit;

/**
 * Öffnungszeiten/ Sprechzeiten eines Arbeitsplatzes (Workplace).
 * Kapselt Darstellung (HTML) und Zugriff auf strukturierte Daten.
 */
class OpeningHours {
    public array $officeHours = [];
    public array $consultationHours = [];
    public ?bool $consultationHoursByAggreement = null;
    public ?string $consultationHoursContactType = null;
    public ?string $consultationHoursContactHint = null;


    public function __construct(array $data = []) {
        if (!empty($data)) {
            $this->fromArray($data);
        }
    }

    /**
     * Setzt alle Felder auf Ausgangswerte zurück.
     * @return void
     */
    public function resetFields(): void {
        $this->officeHours = [];
        $this->consultationHours = [];
        $this->consultationHoursByAggreement = null;
        $this->consultationHoursContactType = null;
        $this->consultationHoursContactHint = null;
    }

    /**
     * Füllt die Eigenschaften aus einem Array (z. B. Workplace-Daten aus der API).
     * Optional: vorher alles leeren.
     * @param array $data Quelle (Keys: officeHours, consultationHours, consultationHoursByAggreement, ...)
     * @param bool $clear Optional (default true): vor dem Befüllen resetten
     * @return self
     */
    public function fromArray(array $data, bool $clear = true): self {
        if ($clear) {
            $this->resetFields();
        }

        if (!empty($data['officeHours']) && is_array($data['officeHours'])) {
            $this->officeHours = $this->sanitizeHoursArray($data['officeHours']);
        }

        if (!empty($data['consultationHours']) && is_array($data['consultationHours'])) {
            $this->consultationHours = $this->sanitizeHoursArray($data['consultationHours'], true);
        }

        if (array_key_exists('consultationHoursByAggreement', $data)) {
            $this->consultationHoursByAggreement = (bool) $data['consultationHoursByAggreement'];
        }

        if (array_key_exists('consultationHoursContactType', $data)) {
            $this->consultationHoursContactType = is_string($data['consultationHoursContactType'])
                ? trim($data['consultationHoursContactType'])
                : null;
        }

        if (array_key_exists('consultationHoursContactHint', $data)) {
            $this->consultationHoursContactHint = is_string($data['consultationHoursContactHint'])
                ? trim($data['consultationHoursContactHint'])
                : null;
        }

        return $this;
    }

    /**
     * Gibt alle Felder als assoziatives Array zurück.
     * @return array{
     *   officeHours: array,
     *   consultationHours: array,
     *   consultationHoursByAggreement: ?bool,
     *   consultationHoursContactType: ?string,
     *   consultationHoursContactHint: ?string
     * }
     */
    public function toArray(): array {
        return [
            'officeHours'                     => $this->officeHours,
            'consultationHours'               => $this->consultationHours,
            'consultationHoursByAggreement'   => $this->consultationHoursByAggreement,
            'consultationHoursContactType'    => $this->consultationHoursContactType,
            'consultationHoursContactHint'    => $this->consultationHoursContactHint,
        ];
    }

    /**
     * Bequemer Getter; liefert $default, falls Key nicht existiert.
     * @param string $key Key-Name (z. B. 'officeHours')
     * @param mixed $default Optionaler Defaultwert
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed {
        return $this->toArray()[$key] ?? $default;
    }

    /**
     * Liefert den HTML-Block für "Sprechstunde nach Vereinbarung".
     * Nutzt die Objektfelder consultationHoursByAggreement, -ContactType, -ContactHint.
     * @return string HTML (leer, wenn nicht zutreffend)
     */
    public function getConsultationbyAggreement(): string {
        if (empty($this->consultationHoursByAggreement)) {
            return '';
        }

        $agreement = '';
        if (!empty($this->consultationHoursContactType)) {
            switch ($this->consultationHoursContactType) {
                case 'mail':
                    $agreement .= __('By appointment', 'rrze-faudir') . ' ' . __('via email', 'rrze-faudir');
                    break;
                case 'phone':
                    $agreement .= __('By appointment', 'rrze-faudir') . ' ' . __('via phone', 'rrze-faudir');
                    break;
                default:
                    $agreement .= __('By appointment', 'rrze-faudir');
            }

            if (!empty($this->consultationHoursContactHint)) {
                $agreement .= ' <span class="ContactHint">' . esc_html($this->consultationHoursContactHint) . '</span>';
            }
        } else {
            // Fallback: kein Type angegeben, aber ByAggreement = true
            $agreement .= __('By appointment', 'rrze-faudir');
        }

        if (!empty($agreement)) {
            return '<p class="consultationHoursByAggreement">' . $agreement . '</p>';
        }
        return '';
    }

    /**
     * Rendert die Öffnungs-/Sprechzeiten als semantisches HTML.
     * Optional kann bereits erzeugtes Adress-HTML angehängt werden.
     * @param string $key Optional ('consultationHours'| 'officeHours'), Standard: 'consultationHours'
     * @param string|null $addressHtml Optional: HTML-Block mit Adressdaten, der angehängt wird
     * @param string $lang Optional: Sprachcode für Wochentage ('de'|'en'), Standard 'de'
     * @return string HTML (leer, wenn keine Zeiten vorhanden)
     */
    public function getConsultationsHours(string $key = 'consultationHours', ?string $addressHtml = null, string $lang = 'de', ?string $label = null): string {
        $list = ($key === 'officeHours') ? $this->officeHours : $this->consultationHours;
        if (empty($list)) {
            return '';
        }

        $output  = '';
        $output .= '<div class="workplace-hours" itemprop="contactPoint" itemscope itemtype="https://schema.org/ContactPoint">';
        
        // Label bestimmen: optionaler Parameter hat Vorrang, sonst Defaults
        $metaLabel = $label ?? (($key === 'officeHours')
            ? esc_html__('Office Hours', 'rrze-faudir')
            : esc_html__('Consultation Hours', 'rrze-faudir'));

        $output .= '<meta itemprop="contactType" content="' . esc_attr($metaLabel) . '">';

        $num = count($list);
        if ($num > 1) {
            $output .= '<ul class="ContactPointList">';
        }

        foreach ($list as $row) {
            $weekday = isset($row['weekday']) ? (int) $row['weekday'] : -1;
            $from    = isset($row['from']) ? (string) $row['from'] : '';
            $to      = isset($row['to']) ? (string) $row['to'] : '';
            $comment = isset($row['comment']) ? (string) $row['comment'] : '';
            $url     = isset($row['url']) ? (string) $row['url'] : '';

            if ($num > 1) {
                $output .= '<li>';
            }

            $output .= '<div class="hoursAvailable" itemprop="hoursAvailable" itemscope itemtype="https://schema.org/OpeningHoursSpecification">';
            $output .= '<span class="weekday" itemprop="dayOfWeek" content="https://schema.org/' . esc_attr(self::getWeekdaySpec($weekday)) . '">';
            $output .= '<span class="dayname">' . esc_html(self::getWeekday($weekday, $lang)) . ': </span>';
            $output .= '<span class="daytime"><span itemprop="opens">' . esc_html($from) . '</span> - ';
            $output .= '<span itemprop="close">' . esc_html($to) . '</span></span>';
            $output .= '</span>';

            if (!empty($comment)) {
                $output .= '<p class="comment" itemprop="description">' . esc_html($comment) . '</p>';
            }
            if (!empty($url)) {
                $output .= '<p class="url" itemprop="url"><a href="' . esc_url($url) . '">' . esc_html($url) . '</a></p>';
            }

            $output .= '</div>';

            if ($num > 1) {
                $output .= '</li>';
            }
        }

        if ($num > 1) {
            $output .= '</ul>';
        }

        if (!empty($addressHtml)) {
            $output .= $addressHtml;
        }

        $output .= '</div>';
        return $output;
    }

    /**
     * Sanitizer für Stunden-Arrays (macht Keys robust, filtert ungültige/inkomplette Einträge).
     * @param array $rows Quelle (Liste von Arrays)
     * @param bool $withOptional Ob optionale Felder (comment|url) erwartet werden
     * @return array Bereinigte Liste
     */
    private function sanitizeHoursArray(array $rows, bool $withOptional = false): array {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (!isset($row['weekday'], $row['from'], $row['to'])) {
                continue;
            }
            $weekday = (int) $row['weekday'];
            if ($weekday < 0 || $weekday > 6) {
                continue;
            }
            $item = [
                'weekday' => $weekday,
                'from'    => (string) $row['from'],
                'to'      => (string) $row['to'],
            ];
            if ($withOptional) {
                if (isset($row['comment']) && $row['comment'] !== '') {
                    $item['comment'] = (string) $row['comment'];
                }
                if (isset($row['url']) && $row['url'] !== '') {
                    $item['url'] = (string) $row['url'];
                }
            }
            $out[] = $item;
        }
        return $out;
    }

    /**
     * Liefert den lokalisierten Namen des Wochentags.
     * @param int $weekday 0..6 (So..Sa)
     * @param string $lang Optional 'de' oder 'en' (bestimmt Übersetzung)
     * @return string
     */
    private static function getWeekday(int $weekday, string $lang = 'de'): string {
        // Wir nutzen die WP-Übersetzungen – die Schlüssel bleiben deutsch/englisch gleich
        $mapDe = [
            0 => __('Sunday','rrze-faudir'),
            1 => __('Monday','rrze-faudir'),
            2 => __('Tuesday','rrze-faudir'),
            3 => __('Wednesday','rrze-faudir'),
            4 => __('Thursday','rrze-faudir'),
            5 => __('Friday','rrze-faudir'),
            6 => __('Saturday','rrze-faudir'),
        ];
        // (Optionaler) eigener Sprachzweig – derzeit identisch, da die Übersetzung via __() erfolgt.
        return $mapDe[$weekday] ?? __('Unknown','rrze-faudir');
    }

    /**
     * Liefert den Schema.org-konformen Wochentag (englischer Bezeichner).
     * @param int $weekday 0..6
     * @return string
     */
    private static function getWeekdaySpec(int $weekday): string {
        $map = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
        return $map[$weekday] ?? 'Unknown';
    }
}
