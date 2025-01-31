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
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            
        ]);
    }

    #[Route('/api/config', name: 'config', methods: ['GET'])]
    public function getSomeValue(): JsonResponse
    {    
        $config = $this->configLoader->getConfig();

        return $this->json([
            'config' => $config
        ]);
    }


    #[Route('/scene/babylon', name: 'scene_babylon')]
    public function babylonScene(): Response
    {
        return $this->render('3Dscene/babylon_scene.html.twig', [
        ]);
    }
}