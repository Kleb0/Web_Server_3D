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

    #[Route('/api/script', name: 'api_script', methods: ['GET'])]
    public function getScript(): Response
    {
        $scriptContent = $this->configLoader->getBabylonScript();

        return new Response($scriptContent, 200, [
            'Content-Type' => 'application/javascript',
        ]);
    }
}