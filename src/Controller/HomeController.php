<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ConfigLoader;
// use App\Service\ConcatenateService;

final class HomeController extends AbstractController
{

    private ConfigLoader $configLoader;
    // private ConcatenateService $concatenateService;

    public function __construct(ConfigLoader $configLoader)
    {
        $this->configLoader = $configLoader;
        // $this->concatenateService = $concatenateService;
    }
    
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // $this->concatenateService->concatenate();
        $config = $this->configLoader->getConfig();
        $scriptContent = $this->configLoader->getBabylonScript();
        $scriptPath = $config['babylonPath'];

        return $this->render('3Dscene/babylon_scene.html.twig', [
            'controller_name' => 'HomeController',
            'scriptPath' => $scriptPath,
            'scriptContent' => json_encode($scriptContent),
            'config' => $config            
            
        ]);
    }

    // we will not use this route anymore but still find a way to return the config

    // // #[Route('/api/config', name: 'api_config', methods: ['GET'])]
    // public function getConfigTest(): JsonResponse
    // {
    //     return $this->json($this->configLoader->getConfig());
    // }

    // we will not use this route anymore too

    // this route will be used to fetch the babylon script from API by reading the file obtained by api_config
    // #[Route('/Babylon.js', name: 'serve_babylon', methods: ['GET'])]
    // public function serveBabylonScript(): Response
    // {
    //     $scriptContent = $this->configLoader->getBabylonScript();

    //     return new Response($scriptContent, 200, [
    //         'Content-Type' => 'application/javascript',
    //     ]);
    // }

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