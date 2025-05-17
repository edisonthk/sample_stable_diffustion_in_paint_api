<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Inpainting Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .upload-section {
            text-align: center;
            margin-bottom: 20px;
            padding: 20px;
            border: 2px dashed #ccc;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .canvas-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        .canvas-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        canvas {
            border: 1px solid #ddd;
            background-color: #fff;
            margin-bottom: 10px;
            max-width: 100%;
        }
        .tools {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            gap: 10px;
        }
        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
        .brush-size {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        .status {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            border-radius: 4px;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
        }
        #submitBtn {
            background-color: #337ab7;
            padding: 12px 24px;
            font-size: 18px;
            margin-top: 10px;
            width: 200px;
        }
        #submitBtn:hover {
            background-color: #286090;
        }
        .instructions {
            margin: 20px 0;
            padding: 15px;
            background-color: #e7f3fe;
            border-left: 6px solid #2196F3;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Image Inpainting Tool</h1>

        <div class="instructions">
            <h3>How to use:</h3>
            <ol>
                <li>Upload an image using the button below</li>
                <li>On the right canvas, brush over areas to create a mask (white areas will be edited)</li>
                <li>You'll see the original image in the background for reference</li>
                <li>Adjust brush size as needed</li>
                <li>Enter a prompt describing what should replace the masked area</li>
                <li>Click "Submit" to process the image and generate a result</li>
                <li>The AI-generated result will appear below</li>
            </ol>
            <p><small>Note: The mask will be converted to pure black and white before processing</small></p>
        </div>

        <div class="upload-section">
            <input type="file" id="imageUpload" accept="image/*" hidden>
            <button id="uploadBtn">Upload Image</button>
            <p id="fileName">No file selected</p>
        </div>

        <div class="canvas-container">
            <div class="canvas-wrapper">
                <h3>Original Image</h3>
                <canvas id="originalCanvas" width="500" height="400"></canvas>
            </div>
            <div class="canvas-wrapper">
                <h3>Mask (White=Area to Edit)</h3>
                <canvas id="maskCanvas" width="500" height="400"></canvas>
                <p><small>Original image shown in background for reference</small></p>
            </div>
        </div>

        <div class="tools">
            <div class="brush-size">
                <label for="brushSize">Brush Size: </label>
                <input type="range" id="brushSize" min="1" max="50" value="20">
                <span id="brushSizeValue">20px</span>
            </div>
            <button id="clearMaskBtn">Clear Mask</button>
        </div>

        <div class="prompt-section" style="margin: 20px 0; padding: 15px; background-color: #f9f9f9; border-radius: 8px;">
            <h3>Image Generation Settings</h3>
            <div style="margin-bottom: 15px;">
                <label for="promptInput" style="display: block; margin-bottom: 5px;">Prompt (what to replace the masked area with):</label>
                <input type="text" id="promptInput" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                    value="Replace with wood bench" placeholder="Describe what should replace the masked area">
            </div>
        </div>

        <div style="text-align: center;">
            <button id="submitBtn">Submit Images</button>
        </div>

        <div id="resultContainer" style="margin-top: 30px; display: none;">
            <h3>Generated Result:</h3>
            <div style="text-align: center;">
                <img id="resultImage" style="max-width: 100%; border: 1px solid #ddd; border-radius: 4px;">
            </div>
        </div>

        <div id="status" class="status" style="display: none;"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DOM Elements
            const uploadBtn = document.getElementById('uploadBtn');
            const imageUpload = document.getElementById('imageUpload');
            const fileName = document.getElementById('fileName');
            const originalCanvas = document.getElementById('originalCanvas');
            const maskCanvas = document.getElementById('maskCanvas');
            const brushSizeInput = document.getElementById('brushSize');
            const brushSizeValue = document.getElementById('brushSizeValue');
            const clearMaskBtn = document.getElementById('clearMaskBtn');
            const submitBtn = document.getElementById('submitBtn');
            const statusDiv = document.getElementById('status');

            // Canvas contexts
            const originalCtx = originalCanvas.getContext('2d');
            const maskCtx = maskCanvas.getContext('2d');

            // Variables
            let uploadedImage = null;
            let isDrawing = false;
            let brushSize = 20;

            // Disable submit button initially
            submitBtn.disabled = true;

            // Event Listeners
            uploadBtn.addEventListener('click', () => imageUpload.click());

            imageUpload.addEventListener('change', handleImageUpload);

            brushSizeInput.addEventListener('input', () => {
                brushSize = brushSizeInput.value;
                brushSizeValue.textContent = brushSize + 'px';
            });

            clearMaskBtn.addEventListener('click', clearMask);

            maskCanvas.addEventListener('mousedown', startDrawing);
            maskCanvas.addEventListener('mousemove', draw);
            maskCanvas.addEventListener('mouseup', stopDrawing);
            maskCanvas.addEventListener('mouseout', stopDrawing);

            // Touch Events for mobile devices
            maskCanvas.addEventListener('touchstart', handleTouchStart);
            maskCanvas.addEventListener('touchmove', handleTouchMove);
            maskCanvas.addEventListener('touchend', stopDrawing);

            submitBtn.addEventListener('click', submitImages);

            // Functions
            function handleImageUpload(e) {
                const file = e.target.files[0];
                if (file) {
                    fileName.textContent = file.name;

                    const reader = new FileReader();
                    reader.onload = function(event) {
                        uploadedImage = new Image();
                        uploadedImage.onload = function() {
                            // Resize canvas to match image aspect ratio
                            resizeCanvases(uploadedImage.width, uploadedImage.height);

                            // Draw original image
                            originalCtx.clearRect(0, 0, originalCanvas.width, originalCanvas.height);
                            originalCtx.drawImage(uploadedImage, 0, 0, originalCanvas.width, originalCanvas.height);

                            // Initialize mask canvas with original image as background
                            initMaskCanvas();

                            // Enable submit button
                            submitBtn.disabled = false;
                        };
                        uploadedImage.src = event.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            }

            function resizeCanvases(width, height) {
                // Calculate new dimensions maintaining aspect ratio
                const maxWidth = 500;
                const maxHeight = 400;

                let newWidth = width;
                let newHeight = height;

                // Resize if image is larger than maxWidth or maxHeight
                if (width > maxWidth || height > maxHeight) {
                    const ratio = Math.min(maxWidth / width, maxHeight / height);
                    newWidth = width * ratio;
                    newHeight = height * ratio;
                }

                // Set canvas sizes
                originalCanvas.width = maskCanvas.width = newWidth;
                originalCanvas.height = maskCanvas.height = newHeight;
            }

            function initMaskCanvas() {
                // First draw the original image as background (with reduced opacity)
                if (uploadedImage) {
                    maskCtx.globalAlpha = 0.3; // Set opacity for background image
                    maskCtx.drawImage(uploadedImage, 0, 0, maskCanvas.width, maskCanvas.height);
                    maskCtx.globalAlpha = 1.0; // Reset opacity
                }

                // Then fill with semi-transparent black as the initial mask state
                maskCtx.fillStyle = 'rgba(0, 0, 0, 0.7)';
                maskCtx.fillRect(0, 0, maskCanvas.width, maskCanvas.height);
            }

            function startDrawing(e) {
                if (!uploadedImage) return;

                isDrawing = true;
                draw(e);
            }

            function draw(e) {
                if (!isDrawing || !uploadedImage) return;

                const x = e.clientX - maskCanvas.getBoundingClientRect().left;
                const y = e.clientY - maskCanvas.getBoundingClientRect().top;

                maskCtx.fillStyle = 'white';
                maskCtx.beginPath();
                maskCtx.arc(x, y, brushSize / 2, 0, Math.PI * 2);
                maskCtx.fill();
            }

            function handleTouchStart(e) {
                e.preventDefault();
                if (!uploadedImage) return;

                isDrawing = true;
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent('mousedown', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                maskCanvas.dispatchEvent(mouseEvent);
            }

            function handleTouchMove(e) {
                e.preventDefault();
                if (!isDrawing) return;

                const touch = e.touches[0];
                const mouseEvent = new MouseEvent('mousemove', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                maskCanvas.dispatchEvent(mouseEvent);
            }

            function stopDrawing() {
                isDrawing = false;
            }

            function clearMask() {
                if (!uploadedImage) return;

                initMaskCanvas();
            }

            function submitImages() {
                if (!uploadedImage) {
                    showStatus('Please upload an image first', 'error');
                    return;
                }

                const prompt = document.getElementById('promptInput').value.trim() || "Replace with wood bench";

                // Show loading status
                showStatus('Processing images...', '');

                // Convert original image to base64
                const originalBase64 = originalCanvas.toDataURL('image/png').split(',')[1];

                // Create a temporary canvas for the pure black and white mask
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = maskCanvas.width;
                tempCanvas.height = maskCanvas.height;
                const tempCtx = tempCanvas.getContext('2d');

                // Draw the mask canvas content to the temp canvas
                tempCtx.drawImage(maskCanvas, 0, 0);

                // Get the image data
                const imageData = tempCtx.getImageData(0, 0, tempCanvas.width, tempCanvas.height);
                const data = imageData.data;

                // Convert to pure black and white (threshold at 128)
                for (let i = 0; i < data.length; i += 4) {
                    // Calculate grayscale value (simple average method)
                    const avg = (data[i] + data[i + 1] + data[i + 2]) / 3;

                    // Set to either pure black or pure white
                    const value = avg >= 128 ? 255 : 0;
                    data[i] = value;     // R
                    data[i + 1] = value; // G
                    data[i + 2] = value; // B
                    data[i + 3] = 255;   // A (fully opaque)
                }

                // Put the modified image data back
                tempCtx.putImageData(imageData, 0, 0);

                // Get the pure black and white mask as base64
                const maskBase64 = tempCanvas.toDataURL('image/png').split(',')[1];

                // Create request payload for our PHP endpoint
                const payload = {
                    originalImage: originalBase64,
                    maskImage: maskBase64,
                    prompt: prompt
                };

                // Send to server via our PHP proxy
                fetch('post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(response => {
                    if (response.ok) {
                        return response.json();
                    }
                    throw new Error('Network response was not ok');
                })
                .then(data => {
                    if (data.status === "success") {
                        showStatus('Image processed successfully!', 'success');

                        // Display the result image
                        const resultContainer = document.getElementById('resultContainer');
                        const resultImage = document.getElementById('resultImage');

                        // Use the first output URL (either regular or proxy link)
                        const imageUrl = data.output[0] || data.proxy_links[0];
                        resultImage.src = imageUrl;
                        resultContainer.style.display = 'block';

                        // Scroll to the result
                        resultContainer.scrollIntoView({ behavior: 'smooth' });

                        // Optionally, show the mask that was sent (for debugging)
                        // const debugImg = document.createElement('img');
                        // debugImg.src = 'data:image/png;base64,' + maskBase64;
                        // document.body.appendChild(debugImg);
                    } else {
                        showStatus('Error processing image: ' + (data.message || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showStatus('Error processing images. Please try again.', 'error');
                });
            }

            function showStatus(message, type) {
                statusDiv.textContent = message;
                statusDiv.className = 'status ' + type;
                statusDiv.style.display = 'block';

                // Hide status after 5 seconds
                setTimeout(() => {
                    statusDiv.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</body>
</html>