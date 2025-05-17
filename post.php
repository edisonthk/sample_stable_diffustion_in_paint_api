<?php
// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get the JSON data sent from the client
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Validate the input data
if (!$data || !isset($data['originalImage']) || !isset($data['maskImage']) || !isset($data['prompt'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit;
}

// Prepare the API request payload
$apiPayload = [
    "key" => env('API_KEY'),
    "model_id" => "v51_inpainting",
    "prompt" => $data['prompt'],
    "negative_prompt" => "(child:1.5), ((((underage)))), ((((child)))), (((kid))), (((preteen))), (teen:1.5) ugly, tiling, poorly drawn hands, poorly drawn feet, poorly drawn face, out of frame, extra limbs, disfigured, deformed, body out of frame, bad anatomy, watermark, signature, cut off, low contrast, underexposed, overexposed, bad art, beginner, amateur, distorted face, blurry, draft, grainy",
    "init_image" => $data['originalImage'],
    "mask_image" => $data['maskImage'],
    "samples" => "1",
    "steps" => "21",
    "safety_checker" => "no",
    "guidance_scale" => 7.5,
    "strength" => 1,
    "scheduler" => "DPMSolverMultistepScheduler",
    "tomesd" => "yes",
    "use_karras_sigmas" => "yes",
    "vae" => null,
    "lora_strength" => null,
    "embeddings_model" => null,
    "seed" => null,
    "webhook" => null,
    "track_id" => null,
    "base64" => true
];

// Initialize cURL session
$ch = curl_init('https://modelslab.com/api/v6/images/inpaint');

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

// Execute the cURL request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for cURL errors
if (curl_errno($ch)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'API request failed: ' . curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}

// Close cURL session
curl_close($ch);

// Forward the API response back to the client
if ($httpCode >= 200 && $httpCode < 300) {
	// Just relay the response from the ModelsLab API
	$responseData = json_decode($response);
	if (isset($responseData->output[0])) {

		$responseData->output[0] = 'data:image/png;base64,'. file_get_contents($responseData->output[0]);
	}

    echo json_encode($responseData);
} else {
    // Something went wrong with the API request
    echo json_encode([
        'status' => 'error',
        'message' => 'API returned error code: ' . $httpCode,
        'response' => json_decode($response, true)
    ]);
}
