// Get the canvas element
const canvas = document.getElementById('renderCanvas');
canvas.style.width = "100%";
canvas.style.height = "100%";

// Create the Babylon.js engine
const engine = new BABYLON.Engine(canvas, true);

// Create the scene
const createScene = () => {
    const scene = new BABYLON.Scene(engine);

    //scene physics
    scene.enablePhysics(new BABYLON.Vector3(0, -9.81, 0), new BABYLON.CannonJSPlugin());

    // Add a camera with FPS style controls
    const camera = new BABYLON.UniversalCamera("FPSCamera", new BABYLON.Vector3(0.5, 1.5, 0.5), scene);
    camera.attachControl(canvas, true);
    camera.speed = 0.2; // Movement speed
    camera.keysUp = [90]; // Z
    camera.keysDown = [83]; // S
    camera.keysLeft = [81]; // Q
    camera.keysRight = [68]; // D
    camera.angularSensibility = 3000;
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

        // Load the glTF
    BABYLON.SceneLoader.Append(
            `/gltf/`, 
            `blender_test.gltf?timestamp=${Date.now()}`,
            scene, () => {
            console.log("GLTF loaded successfully!");

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
                        BABYLON.PhysicsImpostor.BoxImpostor, // Type d'imposteur pour les cubes
                        { mass: 1, restitution: 0.2 }, // Masse et restitution
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

        },         
        null, (scene, message, exception) => {
            console.error( message, exception);
        });
    

    // ---- as we are using physic now, this part is not needed anymore  ---- //
    // let isAscending = false;
    // let isDescending = false;
    // const ascendSpeed = 0.2;


    // const animateVerticalMovement = () => {
    //     if (isAscending) {
    //         camera.position.y += ascendSpeed;
    //     } else if (isDescending) {
    //         camera.position.y -= ascendSpeed;
    //     }
    // };

    // window.addEventListener('keydown', (event) => {
    //     if (event.key === "a" || event.key === "A") {
    //         isAscending = true;
    //     }
    //     else if (event.key === "e" || event.key === "E") {
    //         isDescending = true;
    //     }

    // });

    // window.addEventListener('keyup', (event) => { 
    //     if (event.key === "a" || event.key === "A") {
    //         isAscending = false;
    //     }

    //     else if (event.key === "e" || event.key === "E") {
    //         isDescending = false;
    //     }
    // });



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

    // Handle background color changes
    pickr.on('change', (color) => {
        const rgba = color.toRGBA(); 
        const [r, g, b, a] = rgba; // Decompose RGBA
        scene.clearColor = new BABYLON.Color4(r / 255, g / 255, b / 255, a || 1); // Set clearColor
    });

    // scene.onBeforeRenderObservable.add(animateVerticalMovement);


    let lastHighlightedMesh = null;

    const checkRaycast = () => {
        // Crée un rayon à partir de la caméra
        const ray = camera.getForwardRay();
    
        // Lance le raycast
        const hit = scene.pickWithRay(ray);
    
        if (hit.pickedMesh && hit.pickedMesh.name === "Cone") {
            if (lastHighlightedMesh !== hit.pickedMesh) {
                // Si un nouvel objet est visé, appliquez le matériau rouge
                if (lastHighlightedMesh) {
                    lastHighlightedMesh.material = blueMaterial; // Réinitialise l'ancien mesh
                }
                hit.pickedMesh.material = redMaterial;
                lastHighlightedMesh = hit.pickedMesh;
            }
        } else if (lastHighlightedMesh) {
            // Si aucun objet n'est visé, réinitialise l'ancien mesh
            lastHighlightedMesh.material = blueMaterial;
            lastHighlightedMesh = null;
        }
    };
    

    scene.onBeforeRenderObservable.add(() => {
        checkRaycast();
    });

    return scene;
};

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

// Call the createScene function
const scene = createScene();

// Run the render loop
engine.runRenderLoop(() => {
    scene.render();
});

// Resize the engine on window resize
window.addEventListener('resize', () => {
    engine.resize();
});
