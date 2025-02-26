const InitializeScene = async() => {
    const canvas = document.getElementById('renderCanvas');
    canvas.style.width = "100%";
    canvas.style.height = "100%";

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

    const engine = new BABYLON.Engine(canvas, true, { preserveDrawingBuffer: true, stencil: true });
         const createScene = async () => {
        const scene = new BABYLON.Scene(engine);

        //scene physics
        scene.enablePhysics(new BABYLON.Vector3(0, -9.81, 0), new BABYLON.CannonJSPlugin());

        // Add a camera with FPS style controls
        const camera = new BABYLON.UniversalCamera("FPSCamera", new BABYLON.Vector3(0.5, 1.5, 0.5), scene);
        camera.attachControl(canvas, true);
        
         
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
        camera.ellipsoid = new BABYLON.Vector3(0.5, 1.5, 0.5);

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
                gltfPath + `?timestamp=${Date.now()}`,
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
                            console.log(`Physics impostor applied to ${mesh.name}`);
                        }
                    });
                    
                    const plane = scene.getMeshByName("Plane");
                    if (plane) {
                        // Configure the physics impostor
                        plane.material.backFaceCulling = false;
                        plane.physicsImpostor = new BABYLON.PhysicsImpostor(
                            plane,
                            BABYLON.PhysicsImpostor.BoxImpostor, // Use BoxImpostor for a flat plane
                            { mass: 0, restitution: 0.2 }, // Static object
                            scene
                        );
                        plane.checkCollisions = true; // Enable collision detection
                        console.log("Plane loaded successfully! Physics enabled.");
                    } else {
                        console.error("Plane not found");
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
                }
            } 
            else if (lastHighlightedMesh) 
            {
                // if there is a last mesh highlighted, reset it
                lastHighlightedMesh.material = blueMaterial;
                lastHighlightedMesh = null;
            }
        };        

        scene.onBeforeRenderObservable.add(() => {
            checkRaycast();
        });

        return scene;
    };

    // ------------------ End of Raycasting Logic ------------------ //


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

    
    }

InitializeScene();
