<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class ConcatenateService
{

    private string $configPath;
    private string $babylonScriptPath;
    private string $projectDir;
    private Filesystem $filesystem;

    public function __construct (string $projectDir)
    {
        $this->projectDir = $projectDir;
        $this->configPath = $projectDir . '/src/BabylonJs/config/config.json';
        $this->babylonScriptPath = $projectDir . '/Babylon.js';
        $this->filesystem = new Filesystem();
    }

    public function Concatenate(): void
    {
        if (!file_exists($this->configPath)) {
            throw new \RuntimeException("ERROR ! Config JSON file not found: {$this->configPath}");
        }

        $configContent = file_get_contents($this->configPath);
        $config = json_decode($configContent, true, 512, JSON_THROW_ON_ERROR);

        if (!isset($config['3DfilePath'])) {
            throw new \RuntimeException("ERROR ! Missing '3DfilePath' key in config.json");
        }

        $gltfPath = $config['3DfilePath'];
        $fullGltfPath = $this->projectDir . '/' . ltrim($gltfPath, '/');


        if (!$fullGltfPath || !file_exists($fullGltfPath)) {
            throw new \RuntimeException("ERROR ! The specified 3D file does not exist: {$fullGltfPath}");
        }

        $scriptToAdd = <<<JS

        //get directly the gltf path from the config.json file
        BABYLON.SceneLoader.Append("{$gltfPath}?timestamp=" + Date.now(), "", scene, () => {
            console.log("GLTF loaded successfully from '{$gltfPath}'");
        }, null, (scene, message, exception) => {
            console.error(message, exception);
        });
        JS;


        try{
            $existingContent = file_get_contents($this->babylonScriptPath);

            if (strpos($existingContent, "BABYLON.SceneLoader.Append") !== false) {
                echo " INFO : The Babylon script already contains the 3D file";
                return;
            }

            $updatedContent = $existingContent . "\n" . $scriptToAdd;
            $this->filesystem->dumpFile($this->babylonScriptPath, $updatedContent);

            echo "Content added successfully to Babylon.js";
        }catch (IOExceptionInterface $exception){
            echo "An error occurred while adding content to Babylon.js";
        }      
        

    }

    
}