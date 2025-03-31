<?php

namespace App\Controller;

use App\Service\CreateBabylonFileTeleportTestService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ConfigLoader;
use App\Service\CreateBabylonFileService;
final class HomeController extends AbstractController
{

    private ConfigLoader $configLoader;
    private CreateBabylonFileService $createBabylonFileService;

    public function __construct(ConfigLoader $configLoader, CreateBabylonFileService $createBabylonFileService)
    {
        $this->configLoader = $configLoader;
        $this->createBabylonFileService = $createBabylonFileService;
        $this->createBabylonFileService->createBaseFile();
       
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
}