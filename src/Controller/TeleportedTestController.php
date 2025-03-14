<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ConfigLoader;
use App\Service\CreateBabylonFileTeleportTestService;

final class TeleportedTestController  extends AbstractController 
{

    private ConfigLoader $configLoader;
    private CreateBabylonFileTeleportTestService $createBabylonFileService;

    public function __construct(ConfigLoader $configLoader, CreateBabylonFileTeleportTestService $createBabylonFileTeleportTestService)
    {
        $this->configLoader = $configLoader;
        $this->createBabylonFileService = $createBabylonFileTeleportTestService;
    }

    #[Route('/teleport-test', name: 'teleport_test')]
    public function teleportTest(): Response
    {
        $config = $this->configLoader->getConfig();
        $scriptContent = $this->configLoader->getBabylonScript2();
        $scriptPath = $config['babylonPath2'];

        return $this->render('3Dscene/babylon_teleport_test.html.twig', [
            'controller_name' => 'TeleportedTestController',
            'scriptPath' => $scriptPath,
            'scriptContent' => json_encode($scriptContent),
            'config' => $config
        ]);
    }


    #[Route('/public/gltf/{filename}', name: 'serve_gltf', methods: ['GET'])]
    public function serveGltfFile(string $filename): Response
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/gltf/' . $filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException("File not found: $filename");
        }

        return new Response(file_get_contents($filePath), 200, [
            'Content-Type' => 'model/gltf+json',
        ]);
    }

}