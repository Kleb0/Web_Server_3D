<?php

//declare this php file as a part of the App\Service namespace
namespace App\Service;

//so the class is now considered as a service
class ConfigLoader
{
    private string $configPath;
    private string $babylonPath;
    private string $babylonPath2;
    private string $configFile;

    public function __construct(string $projectDir)
    {
        $this->configPath = $projectDir . '/src/BabylonJs/config/config.json';
        $this->babylonPath = $projectDir . '/GeneratedBabylon.js';
        $this->babylonPath2 = $projectDir . '/GeneratedTeleportTestBabylon.js';
    }

    public function getConfig(): array
    {
        if (!file_exists($this->configPath)) {
            throw new \RuntimeException("Configuration file not found: {$this->configPath}");
        }

        $configContent = file_get_contents($this->configPath);
        $config = json_decode($configContent, true, 512, JSON_THROW_ON_ERROR);

        return $config;
    }

    // the babylon script will be served to the front end from home controller
    public function getBabylonScript(): string
    {
        if (!file_exists($this->babylonPath)) {
            throw new \RuntimeException("BabylonJs file not found: {$this->babylonPath}");
        }

        return file_get_contents($this->babylonPath);
    }

    public function getBabylonScript2(): string
    {
        if (!file_exists($this->babylonPath2)) {
            throw new \RuntimeException("BabylonJs file not found: {$this->babylonPath2}");
        }

        return file_get_contents($this->babylonPath2);
    }
}