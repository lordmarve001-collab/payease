<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Helpers\PhoneNumberHelper;
use Illuminate\Support\Facades\Hash;

// Try to login as "Greate Onyekachukwu" (7060441705) - the agent created by ajo owner
$phone = '7060441705';
$normalizedPhone = PhoneNumberHelper::normalize($phone);

echo "Normalized phone: {$normalizedPhone}\n";

$user = User::where('phone_number', $normalizedPhone)->first();
if (!$user) {
    echo "User not found by normalized phone!\n";
    exit(1);
}

echo "Found user: {$user->full_name}\n";
echo "Phone in DB: {$user->phone_number}\n";
echo "pin_hash present: " . var_export(filled($user->pin_hash), true) . "\n";
echo "Status: {$user->status}\n";
echo "Roles: {$user->getRoleNames()->implode(', ')}\n";

// Now check: what happens if phone was saved as something different?
$rawUser = User::where('phone_number', '0' . $normalizedPhone)->first();
echo "User with leading 0: " . var_export($rawUser !== null, true) . "\n";

// Check all agents and their phone format
echo "\n--- All Agent Users ---\n";
$agents = User::role('agent')->get();
foreach ($agents as $u) {
    echo "  {$u->full_name} | phone: {$u->phone_number} | pin_hash: " . (filled($u->pin_hash) ? 'SET' : 'NULL') . "\n";
}

// Now let's check the 3 unroled agents
echo "\n--- Agents without role (not loginable?) ---\n";
$allAgents = User::whereHas('agent', fn($q) => $q->whereNotNull('id'))->get();
foreach ($allAgents as $u) {
    $roles = $u->getRoleNames()->implode(', ');
    echo "  {$u->full_name} | phone: {$u->phone_number} | roles: {$roles}\n";
}
