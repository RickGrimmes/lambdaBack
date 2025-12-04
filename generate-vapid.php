<?php

require __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\VAPID;

try {
    $keys = VAPID::createVapidKeys();
    
    echo "VAPID_PUBLIC_KEY=" . $keys['publicKey'] . PHP_EOL;
    echo "VAPID_PRIVATE_KEY=" . $keys['privateKey'] . PHP_EOL;
    echo "VAPID_SUBJECT=mailto:admin@lambda.com" . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}
