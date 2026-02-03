<?php

namespace App\Services\Sync;

/**
 * Service to track sync context and prevent infinite loops.
 */
class SyncContext
{
    protected ?string $source = null;
    protected bool $inSync = false;

    /**
     * Set the current sync source.
     *
     * @param string $source 'wp' or 'laravel'
     */
    public function setSource(string $source): void
    {
        $this->source = $source;
        $this->inSync = true;
    }

    /**
     * Get the current sync source.
     *
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * Check if currently in a sync operation.
     *
     * @return bool
     */
    public function isInSync(): bool
    {
        return $this->inSync;
    }

    /**
     * Check if source is WordPress.
     *
     * @return bool
     */
    public function isFromWp(): bool
    {
        return $this->source === 'wp';
    }

    /**
     * Clear sync context.
     */
    public function clear(): void
    {
        $this->source = null;
        $this->inSync = false;
    }
}
