// Get the canvas element
const canvas = document.getElementById('renderCanvas');
canvas.style.width = "100%";
canvas.style.height = "100%";

// Create the Babylon.js engine
const engine = new BABYLON.Engine(canvas, true);

// Create the scene
const createScene = () => {
    const scene = new BABYLON.Scene(engine);

    // Add a camera with FPS style controls
    const camera = new BABYLON.UniversalCamera("FPSCamera", new BABYLON.Vector3(0, 2, 0), scene);
    camera.attachControl(canvas, true);
    camera.speed = 0.2; // Movement speed
    camera.keysUp = [90]; // Z
    camera.keysDown = [83]; // S
    camera.keysLeft = [81]; // Q
    camera.keysRight = [68]; // D
    camera.angularSensibility = 1500;

    // Add a hemispheric light
    const light = new BABYLON.HemisphericLight("light", new BABYLON.Vector3(1, 1, 0), scene);
    light.intensity = 1.0;


    let isAscending = false;
    let isDescending = false;
    const ascendSpeed = 0.2;


    const animateVerticalMovement = () => {
        if (isAscending) {
            camera.position.y += ascendSpeed;
        } else if (isDescending) {
            camera.position.y -= ascendSpeed;
        }
    };

    window.addEventListener('keydown', (event) => {
        if (event.key === "a" || event.key === "A") {
            isAscending = true;
        }
        else if (event.key === "e" || event.key === "E") {
            isDescending = true;
        }

    });

    window.addEventListener('keyup', (event) => { 
        if (event.key === "a" || event.key === "A") {
            isAscending = false;
        }

        else if (event.key === "e" || event.key === "E") {
            isDescending = false;
        }
    });

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

    scene.onBeforeRenderObservable.add(animateVerticalMovement);

    // Load the glTF
    BABYLON.SceneLoader.Append("/gltf/", "blender_test.gltf", scene, () => {
        console.log("GLTF loaded successfully!");
    }, null, (scene, message, exception) => {
        console.error("Failed to load GLTF file:", message, exception);
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
