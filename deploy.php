<?php
// deploy.php - GitHub webhook deployment script
// Place this file in your public_html directory
// Set webhook in GitHub: Settings → Webhooks → Add webhook
// Payload URL: https://yourdomain.infinityfree.com/deploy.php

define('SECRET_TOKEN', 'InfinityFreeToken'); // Change this!

// Verify webhook is from GitHub
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (!verify_github_webhook($payload, $signature)) {
    http_response_code(401);
    die('Unauthorized');
}

$data = json_decode($payload, true);

// Only deploy on push to main branch
if ($data['ref'] !== 'refs/heads/main') {
    die('Not main branch');
}

// Pull latest changes from GitHub
$output = shell_exec('cd ' . __DIR__ . ' && git pull origin main 2>&1');

// Log deployment
file_put_contents(__DIR__ . '/deploy.log', date('Y-m-d H:i:s') . " - Deployed\n" . $output . "\n\n", FILE_APPEND);

echo "Deployment successful\n";

function verify_github_webhook($payload, $signature) {
    if (empty($signature)) {
        return false;
    }
    
    $algo = 'sha256';
    $hash = hash_hmac($algo, $payload, SECRET_TOKEN, false);
    $expected = $algo . '=' . $hash;
    
    return hash_equals($expected, $signature);
}
?>
