<?php

namespace App\DTOs;

class PackageState
{
    public function __construct(
        public array $tour,
        public array $session,
        public array $includedCounters,
        public array $pricing,
        public array $days,
        public array $catalog = [],
    ) {}

    /**
     * Convert to array for JSON response.
     */
    public function toArray(): array
    {
        return [
            'tour' => $this->tour,
            'session' => $this->session,
            'included_counters' => $this->includedCounters,
            'pricing' => $this->pricing,
            'days' => $this->days,
            'catalog' => $this->catalog,
        ];
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
