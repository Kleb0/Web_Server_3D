<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class CreateBabylonFileService
{
    private Filesystem $filesystem;
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->filesystem = new Filesystem();
        $this->projectDir = $projectDir;
    }

    public function configInit(): string
    {
        return <<<JS
    const config = {
        camera: {
            speed: 0.2,
            keysUp: [90],     // Z
            keysDown: [83],   // S
            keysLeft: [81],   // Q
            keysRight: [68],  // D
            angularSensibility: 3000
            }
        };        
JS;
    }

    public function configEngine(): string
    {
        return <<<JS
    const engine = new BABYLON.Engine(canvas, true, { preserveDrawingBuffer: true, stencil: true });
JS;
    }

    public function createScene(): string
    {
        return <<<JS
         const createScene = async () => {
        const scene = new BABYLON.Scene(engine);

        //scene physics
        scene.enablePhysics(new BABYLON.Vector3(0, -9.81, 0), new BABYLON.CannonJSPlugin());

        // Add a camera with FPS style controls
        const camera = new BABYLON.UniversalCamera("FPSCamera", new BABYLON.Vector3(0.5, 1.5, 0.5), scene);
         
        // Assign camera settings from config the camera 
          if (config.camera) {
            Object.assign(camera, config.camera);
            console.log("Camera settings applied:", config.camera);
        } else {
            console.warn("No camera config found.");
        }

        camera.minz = 1;
        camera.maxz = 1000;

        //enable physics on camera
        camera.checkCollisions = true;
        camera.applyGravity = true;
        camera.ellipsoid = new BABYLON.Vector3(1, 1, 1);
        camera.attachControl(canvas, true);

        // Add a hemispheric light
        const light = new BABYLON.HemisphericLight("light", new BABYLON.Vector3(1, 1, 0), scene);
        light.intensity = 1.0;

        //define basic materials
        const redMaterial = new BABYLON.StandardMaterial("redMaterial", scene);
        redMaterial.diffuseColor = BABYLON.Color3.Red();

        const blueMaterial = new BABYLON.StandardMaterial("blueMaterial", scene);
        blueMaterial.diffuseColor = BABYLON.Color3.Blue();



        // ------------------ 3D file loading ------------------ //
        //get the path here 

        try {
        
            const gltfPath = window.sceneConfig["3DfilePath"];

            console.log("Loading GLTF path from Twig:", gltfPath);

            if (!gltfPath || gltfPath.includes("{{")) {
                throw new Error("Erreur: config['3DfilePath'] n'a pas été correctement injecté par Twig.");
            }

            BABYLON.SceneLoader.Append(
                gltfPath + `?timestamp=\${Date.now()}`,
                "",
                scene,
                () => {
    
                    // ------------- Physics Impostors ------------------ //
                    scene.executeWhenReady(()=> {
                        const cone = scene.getMeshByName("Cone");
                        if(cone)
                        {
                            cone.material = blueMaterial;
                            console.log("blue material applied to Cone");
                        }
                        else
                        {
                            console.error("Cone not found");
    
                        }
                    })
    
                    scene.meshes.forEach((mesh) => {
                        if (mesh.name.startsWith("Cube")) {
                            mesh.material.backFaceCulling = false;
                            mesh.physicsImpostor = new BABYLON.PhysicsImpostor(
                                mesh,
                                BABYLON.PhysicsImpostor.BoxImpostor, 
                                { mass: 1, restitution: 0.2 }, 
                                scene
                            );
                            mesh.checkCollisions = true; // Activer les collisions
                            console.log(`Physics impostor applied to \${mesh.name}`);
                        }
                    });
                    
                    const ground = scene.getMeshByName("Ground");
                    if (ground) {
                        // Configure the physics impostor
                        ground.material.backFaceCulling = false;
                        ground.physicsImpostor = new BABYLON.PhysicsImpostor(
                            ground,
                            BABYLON.PhysicsImpostor.BoxImpostor, // Use BoxImpostor for a flat plane
                            { mass: 0, restitution: 0.2 }, // Static object
                            scene
                        );
                        ground.checkCollisions = true; // Enable collision detection
                        console.log("Plane loaded successfully! Physics enabled.");
                    } else {
                        console.error("Plane not found");
                    }

                    const teleport = scene.getMeshByName("teleport");
                    if (teleport)
                    {
                        console.log("Teleport found");
                        teleport.material = redMaterial;
                        teleport.physicsImpostor = new BABYLON.PhysicsImpostor(
                            teleport,
                            BABYLON.PhysicsImpostor.BoxImpostor,
                            { mass: 0, restitution: 0.2 },
                            scene
                        );
                        teleport.checkCollisions = true;
                        console.log("Physics impostor applied to teleport");
                        

       
                    } else {
                        console.error("Teleport not found");
                    } 

         
                    // ------------------ End of Physics Impostors ------------------ //
                },         
                null, (scene, message, exception) => {
                    console.error( message, exception);
                }
            );
        } 
        catch (error) {
            console.error("Failed to load the glTF:", error);           

        }

        // ---- end of 3D file loading ------------------ //

    //---- end of scene creation ------------------------------- //

   // ---- additonal features --------------------------------------- //          

    // --------- Background color and brightness control ------------------ //    
        // Initial background color
        scene.clearColor = new BABYLON.Color4(0.8, 0.8, 0.8, 1);

        // Handle brightness changes
        const brightnessInput = document.getElementById("brightnessInput");
        brightnessInput.addEventListener("input", () => {
            light.intensity = parseFloat(brightnessInput.value);
        });

        // Initialize Pickr (color picker) for background color
        
        const pickr = Pickr.create({
            el: '#color-picker',
            theme: 'nano', 
            default: 'rgb(204, 204, 204)', 
            components: {
            
                preview: true,
                opacity: false,
                hue: true,
                interaction: {
                    input: true,
                    save: true
                }
            }
        });

        pickr.on('change', (color) => {
            const rgba = color.toRGBA(); 
            const [r, g, b, a] = rgba; // Decompose RGBA
            scene.clearColor = new BABYLON.Color4(r / 255, g / 255, b / 255, a || 1); // Set clearColor
        });

    // ---------------- end of background color and brightness control ------------------ //


    // ----------------------- Raycasting Logic ------------------ //

        let lastHighlightedMesh = null;

        const checkRaycast = () => {
           
            //declare the raycast
            const ray = camera.getForwardRay();
        
            //launch the raycast by declaring a collision with it
            const hit = scene.pickWithRay(ray);
        
            if (hit.pickedMesh && hit.pickedMesh.name === "Cone") {
                if (lastHighlightedMesh !== hit.pickedMesh) {
                    
                    if (lastHighlightedMesh) {
                        lastHighlightedMesh.material = blueMaterial; //reset the last mesh
                    }
                    hit.pickedMesh.material = redMaterial;
                    lastHighlightedMesh = hit.pickedMesh;
                    // alert("You have touched on the Cone");
                }
            } 
            else if (lastHighlightedMesh) 
            {
                // if there is a last mesh highlighted, reset it
                lastHighlightedMesh.material = blueMaterial;
                lastHighlightedMesh = null;
            }
        };  
        
        // ------------------ End of Raycasting Logic ------------------ //  
        
        // ----------- Check Collision with Teleport Logic ------------------ //


        const checkCollisionWithTeleport = () => {
            const teleport = scene.getMeshByName("teleport");
            
            if (teleport){
                // alert("Physics Impostor applied to cameraMesh");
                const cameraPosition = camera.position;
                const teleportPosition = teleport.position;

                // console.log("Teleport Position:", teleportPosition);

                const distance = BABYLON.Vector3.Distance(cameraPosition, teleportPosition);
                // console.log("Distance to teleport:", distance);
                if (distance < 20) {
                    const forward = camera.getForwardRay().direction.clone();
                    forward.normalize();

                    const toTeleport = teleportPosition.subtract(cameraPosition).normalize();

                    const dot = BABYLON.Vector3.Dot(forward, toTeleport);
                    // console.log("Dot product:", dot);
                    if (dot < 0.6) {
                        alert("You have been teleported");
                        window.location.href = '/teleport-test';
                    }
                }
            }
        };
    



    // ----------- End of Check Collision with Teleport Logic ------------------ //

        scene.onBeforeRenderObservable.add(() => {
            checkRaycast();
            checkCollisionWithTeleport();
        });

        return scene;
    };




    // --------- Enable Pointer Lock ------------------ //

        // Enable pointer lock on click
        const enablePointerLock = () => {
            canvas.requestPointerLock =
                canvas.requestPointerLock || canvas.mozRequestPointerLock || canvas.webkitRequestPointerLock;

            if (canvas.requestPointerLock) {
                canvas.requestPointerLock();
            }
        };

        canvas.addEventListener("click", enablePointerLock);

        // Handle pointer lock change
        document.addEventListener("pointerlockchange", () => {
            if (document.pointerLockElement === canvas) {
                console.log("Pointer locked.");
            } else {
                console.log("Pointer unlocked.");
            }
        });

    // --------- End of Pointer Lock ------------------ //


    // ------------- end of additional features ------------------------------- //

        // --------- Create the Scene ------------------ //

        // Call the createScene function
        createScene().then((scene) => {
            if(!scene)
                {
                    throw new Error("Failed to create the scene");
            }

            // scene.debugLayer.show({
            //     embedMode: false
            // });

            engine.runRenderLoop(() => {
                scene.render();
            });
        }).catch((error) => {
            console.error("Failed to create the scene:", error);
        });

        // Resize the engine on window resize
        window.addEventListener('resize', () => {
            engine.resize();
        });    

 // ------------------ 3D file loading End ------------------ //

    
JS;
    }

     public function createTestFile(): void
     {
         $filePath = $this->projectDir . '/test.js';
     
         $fileContent = <<<JS
     console.log("Hello from Babylon.js");
     JS;
     
         try {
             $this->filesystem->dumpFile($filePath, $fileContent);
         } catch (IOExceptionInterface $exception) {
             throw new \RuntimeException('An error occurred while creating the file: ' . $exception->getMessage());
         }
     }


    public function createBaseFile(): void
    {

        $filePath =$this->projectDir. '/GeneratedBabylon.js';
        $configInit = $this->configInit();
        $configEngine = $this->configEngine();
        $createScene = $this->createScene();
       

        $fileContent = <<<JS
const InitializeScene = async() => {
    const canvas = document.getElementById('renderCanvas');
    canvas.style.width = "100%";
    canvas.style.height = "100%";

$configInit

$configEngine
$createScene
    }

InitializeScene();

JS;

        try {
            $this->filesystem->dumpFile($filePath, $fileContent);
        } catch (IOExceptionInterface $exception) {
            throw new \RuntimeException('An error occurred while creating the file: ' . $exception->getMessage());
        }
      
    }
}