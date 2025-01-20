// main.js

// Get the canvas element
const canvas = document.getElementById('renderCanvas');
canvas.style.width = "100%";
canvas.style.height = "100%";

// Create the Babylon.js engine
const engine = new BABYLON.Engine(canvas, true);

// Create a basic scene
const createScene = () => {
    const scene = new BABYLON.Scene(engine);

    // Add a camera
    const camera = new BABYLON.ArcRotateCamera("Camera", Math.PI / -8, Math.PI / 2.5, 10, BABYLON.Vector3.Zero(), scene);
    camera.attachControl(canvas, true);

    // Add a directional light for shadows
    const shadowLight = new BABYLON.DirectionalLight("shadowLight", new BABYLON.Vector3(-1, -2, -1), scene);
    shadowLight.intensity = 0.8; // Reduce light intensity
    shadowLight.position = new BABYLON.Vector3(1, 10, 5);

    // Create a shadow generator
    const shadowGenerator = new BABYLON.ShadowGenerator(1024, shadowLight);
    shadowGenerator.useBlurExponentialShadowMap = true;
    shadowGenerator.blurKernel = 64;
    shadowGenerator.useKernelBlur = true;
    shadowGenerator.darkness = 0; 

    // Add a sphere
    const sphere = BABYLON.MeshBuilder.CreateSphere("sphere", { diameter: 2 }, scene);
    sphere.position.y = 1; // Lift the sphere above the ground
    shadowGenerator.addShadowCaster(sphere);

    // Add a ground
    const ground = BABYLON.MeshBuilder.CreateGround("ground", { width: 10, height: 10 }, scene);
    ground.receiveShadows = true;

    // Set ground material to disable backface transparency
    const groundMaterial = new BABYLON.StandardMaterial("groundMaterial", scene);
    groundMaterial.backFaceCulling = false;
    groundMaterial.specularColor = BABYLON.Color3.Black();
    ground.material = groundMaterial;

    let isCPressed = false;
    let lightAngle = 0;

    window.addEventListener('keydown', (event) => {
        if (event.key === 'c' || event.key === 'C') {
            isCPressed = true;
        }
    });

    window.addEventListener('keyup', (event) => {
        if (event.key === 'c' || event.key === 'C') {
            isCPressed = false;
        }
    });

    let isDPressed = false;

    window.addEventListener('keydown', (event) => {
        if (event.key === 'd' || event.key === 'D') {
            isDPressed = true;
        }
    });

    window.addEventListener('keyup', (event) => {
        if (event.key === 'd' || event.key === 'D') {
            isDPressed = false;
        }
    });

    engine.runRenderLoop(() => {
        if (isCPressed) {
            lightAngle += 0.05; // Increment the angle
            shadowLight.position.x = 10 * Math.cos(lightAngle);
            shadowLight.position.z = 10 * Math.sin(lightAngle);
            shadowLight.direction = BABYLON.Vector3.Zero().subtract(shadowLight.position).normalize();
        } else if (isDPressed) {
            lightAngle -= 0.05;
            shadowLight.position.x = 10 * Math.cos(lightAngle);
            shadowLight.position.z = 10 * Math.sin(lightAngle);
            shadowLight.direction = BABYLON.Vector3.Zero().subtract(shadowLight.position).normalize();
        }
        scene.render();
    });

    // Handle background color change using Pickr
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize Pickr
        const pickr = Pickr.create({
            el: '#color-picker',
            theme: 'nano',
            default: '#ffffff',
            components: {
                preview: true,
                opacity: true,
                hue: true,
                interaction: {
                    input: true,
                    save: true
                }
            }
        });
    
        pickr.on('change', (color) => {
            const rgba = color.toRGBA();
            if (window.currentScene) {
                window.currentScene.clearColor = new BABYLON.Color4(rgba[0] / 255, rgba[1] / 255, rgba[2] / 255, rgba[3]);
            }
        });
    });
    


    // Handle brightness change
    const brightnessInput = document.getElementById('brightnessInput');
    brightnessInput.addEventListener('input', (event) => {
        const brightness = parseFloat(event.target.value);
        scene.clearColor.r = Math.min(scene.clearColor.r * brightness, 1);
        scene.clearColor.g = Math.min(scene.clearColor.g * brightness, 1);
        scene.clearColor.b = Math.min(scene.clearColor.b * brightness, 1);
    });

    return scene;
};

// Call the createScene function
const scene = createScene();
window.currentScene = scene; // Make the scene globally accessible for Pickr

// Resize the engine on window resize
window.addEventListener('resize', () => {
    engine.resize();
});