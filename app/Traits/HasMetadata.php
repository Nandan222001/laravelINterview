<?php

namespace App\Traits;

trait HasMetadata
{
    public function getMetadata(string $key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }

    public function setMetadata(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        $this->metadata = $metadata;
        $this->save();
    }

    public function hasMetadata(string $key): bool
    {
        return data_get($this->metadata, $key) !== null;
    }

    public function removeMetadata(string $key): void
    {
        $metadata = $this->metadata ?? [];
        data_forget($metadata, $key);
        $this->metadata = $metadata;
        $this->save();
    }

    public function mergeMetadata(array $data): void
    {
        $this->metadata = array_merge($this->metadata ?? [], $data);
        $this->save();
    }

    public function clearMetadata(): void
    {
        $this->metadata = null;
        $this->save();
    }
}
