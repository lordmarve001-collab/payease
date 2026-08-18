<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Login as agent
$request = \Illuminate\Http\Request::create('/login', 'POST', [
    'phone_number' => '8012345002',
    'pin' => '1234',
]);
$response = $kernel->handle($request);

// Now hit the dashboard
$request2 = \Illuminate\Http\Request::create('/ajo-agent/dashboard', 'GET');
$cookies = $response->headers->getCookies();
foreach ($cookies as $cookie) {
    $request2->cookies->set($cookie->getName(), $cookie->getValue());
}
$response2 = $kernel->handle($request2);
echo "Status: " . $response2->getStatusCode() . "\n";
echo substr($response2->getContent(), 0, 500);
