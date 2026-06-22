<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Parser for WordPress tours_program <-> Laravel program tables
 */
class WpToursProgramParser
{
    /**
     * Parse WP tours_program meta to Laravel format
     * 
     * WP format can be:
     * - Serialized array of days
     * - HTML content
     * - JSON
     */
    public function parseWpProgramToLaravel($wpProgram): array
    {
        if (empty($wpProgram)) {
            return [];
        }

        // Try unserialize
        if (is_string($wpProgram) && preg_match('/^[aOs]:\d+:/', $wpProgram)) {
            $unserialized = @unserialize($wpProgram);
            if ($unserialized !== false && is_array($unserialized)) {
                return $this->normalizeWpProgramArray($unserialized);
            }
        }

        // Try JSON
        if (is_string($wpProgram) && str_starts_with(trim($wpProgram), '{')) {
            $decoded = json_decode($wpProgram, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->normalizeWpProgramArray($decoded);
            }
        }

        // Try as plain text (split by day)
        if (is_string($wpProgram)) {
            return $this->parseTextProgram($wpProgram);
        }

        return [];
    }

    /**
     * Normalize WP program array to standard format
     */
    protected function normalizeWpProgramArray(array $program): array
    {
        $normalized = [];
        $dayNumber = 1;

        foreach ($program as $index => $day) {
            if (is_array($day)) {
                $normalized[] = [
                    'day_number' => $day['day'] ?? $day['day_number'] ?? $dayNumber,
                    'title' => $day['title'] ?? "Jour {$dayNumber}",
                    'description' => $day['description'] ?? $day['content'] ?? $day['desc'] ?? '',
                    'items' => $this->extractDayItems($day),
                ];
            } elseif (is_string($day)) {
                $normalized[] = [
                    'day_number' => $dayNumber,
                    'title' => "Jour {$dayNumber}",
                    'description' => $day,
                    'items' => [],
                ];
            }
            $dayNumber++;
        }

        return $normalized;
    }

    /**
     * Extract day items from day data
     */
    protected function extractDayItems(array $day): array
    {
        $items = [];

        // Check for structured items
        if (isset($day['items']) && is_array($day['items'])) {
            foreach ($day['items'] as $item) {
                $items[] = [
                    'type' => $item['type'] ?? 'activity',
                    'title' => $item['title'] ?? '',
                    'description' => $item['description'] ?? '',
                    'time' => $item['time'] ?? null,
                ];
            }
        }

        return $items;
    }

    /**
     * Parse plain text program (fallback)
     */
    protected function parseTextProgram(string $text): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        $days = [];
        $currentDay = null;
        $dayNumber = 1;

        foreach ($lines as $line) {
            // Detect day headers (e.g., "Day 1", "Jour 1", etc.)
            if (preg_match('/^(day|jour)\s*(\d+)/i', $line, $matches)) {
                if ($currentDay) {
                    $days[] = $currentDay;
                }
                $dayNumber = (int)$matches[2];
                $currentDay = [
                    'day_number' => $dayNumber,
                    'title' => $line,
                    'description' => '',
                    'items' => [],
                ];
            } elseif ($currentDay) {
                $currentDay['description'] .= $line . "\n";
            }
        }

        if ($currentDay) {
            $days[] = $currentDay;
        }

        return $days;
    }

    /**
     * Generate WP tours_program from Laravel program days
     */
    public function generateWpProgramFromLaravel(Collection $programDays): string
    {
        $programArray = [];

        foreach ($programDays as $day) {
            $contentHtml = trim((string) ($day->content_html ?? ''));
            $plainDescription = trim((string) ($day->description ?? ''));

            $dayData = [
                'title' => $day->title ?: "Jour {$day->day_number}",
                'content' => $contentHtml !== '' ? $contentHtml : $plainDescription,
                'description' => $plainDescription,
            ];

            // Add items if any
            if ($day->relationLoaded('dayItems') && $day->dayItems->isNotEmpty()) {
                $dayData['items'] = [];
                foreach ($day->dayItems as $item) {
                    $dayData['items'][] = [
                        'type' => $item->type ?? 'activity',
                        'title' => $item->title ?? '',
                        'description' => $item->description ?? '',
                        'time' => $item->time,
                    ];
                }
            }

            $programArray[] = $dayData;
        }

        // Return serialized (WP standard format)
        return serialize($programArray);
    }
}
