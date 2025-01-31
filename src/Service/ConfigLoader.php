<?php

namespace App\Service;

class ConfigLoader
{
    private string $configPath;

    public function __construct(string $projectDir)
    {
        $this->configPath = $projectDir . '/public/config/config.json';
    }

    public function getConfig(): array
    {
        if (!file_exists($this->configPath)) {
            throw new \RuntimeException("Configuration file not found: {$this->configPath}");
        }

        $configContent = file_get_contents($this->configPath);
        return json_decode($configContent, true, 512, JSON_THROW_ON_ERROR);
    }
}
