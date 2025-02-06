<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ConfigLoader;
use Symfony\Component\HttpFoundation\JsonResponse;

final class HomeController extends AbstractController
{

    private ConfigLoader $configLoader;
    public function __construct(ConfigLoader $configLoader)
    {
        $this->configLoader = $configLoader;
    }
    
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $scriptContent = $this->configLoader->getBabylonScript();

        return $this->render('3Dscene/babylon_scene.html.twig', [
            'controller_name' => 'HomeController',
            'scriptContent' => json_encode($scriptContent),
            
            
        ]);
    }

    #[Route('/api/config', name: 'api_config', methods: ['GET'])]
    public function getConfig(): JsonResponse
    {
        return $this->json($this->configLoader->getConfig());
    }


    // this route will be used to fetch the babylon script from API by reading the file obtained by api_config
    #[Route('/Babylon.js', name: 'serve_babylon', methods: ['GET'])]
    public function serveBabylonScript(): Response
    {
        $scriptContent = $this->configLoader->getBabylonScript();

        return new Response($scriptContent, 200, [
            'Content-Type' => 'application/javascript',
        ]);
    }
}