<?php
require 'vendor/autoload.php';

\Stripe\Stripe::setApiKey('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'); // use your real secret key

$endpoint_secret = 'whsec_9dX3dq8EZhMWh51jPseq4xAEYJdW4qVJ';

$domain = 'https://pagewatch.io'; // your domain
$starter_price_id = 'price_1RtFyEF7ESP6Bszjk2K8pvow';
$pro_price_id = 'price_1RtFyxF7ESP6BszjFdydbnAG';
