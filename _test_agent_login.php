<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Agent;
use App\Models\AjoOwner;
use App\Helpers\PhoneNumberHelper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

// Find an Ajo Owner
$ajoOwner = AjoOwner::first();
if (!$ajoOwner) {
    echo "No Ajo Owner found!\n";
    exit(1);
}
$ownerUser = $ajoOwner->user;
echo "Ajo Owner: {$ownerUser->full_name} ({$ownerUser->phone_number})\n\n";

// Create a test agent
$testPhone = '80' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
$testPin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$normalizedPhone = PhoneNumberHelper::normalize($testPhone);

echo "Creating agent with phone: {$testPhone} (normalized: {$normalizedPhone})\n";
echo "Generated PIN: {$testPin}\n";

$agentUser = User::create([
    'full_name' => 'Test Login Agent',
    'phone_number' => $normalizedPhone,
    'email' => 'testloginagent' . time() . '@payease.local',
    'pin_hash' => Hash::make($testPin, ['rounds' => 12]),
    'status' => 'active',
    'kyc_level' => 0,
]);
$agentUser->assignRole('agent');

$agent = Agent::create([
    'user_id' => $agentUser->id,
    'ajo_owner_id' => $ajoOwner->id,
    'business_name' => 'Test Login Agent Shop',
    'business_address' => '',
    'gps_latitude' => 0,
    'gps_longitude' => 0,
    'lga' => 'Test LGA',
    'state' => 'Lagos',
    'float_balance' => 0,
    'max_float' => 100000,
    'commission_rate' => 1.5,
    'total_earnings' => 0,
    'status' => 'active',
    'approved_at' => now(),
]);

echo "Agent created: {$agentUser->id}\n";
echo "Phone in DB: {$agentUser->phone_number}\n";
echo "pin_hash: {$agentUser->pin_hash}\n";
echo "Status: {$agentUser->status}\n";
echo "Roles: {$agentUser->getRoleNames()->implode(', ')}\n";

// Now simulate login
echo "\n=== Simulating Login ===\n";
$rawPhone = $testPhone;
$stripped = preg_replace('/\D/', '', $rawPhone);
$normalized = PhoneNumberHelper::normalize($stripped);

echo "Input phone: {$rawPhone}\n";
echo "After strip non-digits: {$stripped}\n";
echo "After normalize: {$normalized}\n";

$foundUser = User::where('phone_number', $normalized)->first()
    ?? User::where('phone_number', trim($stripped))->first();

if (!$foundUser) {
    echo "FAIL: User not found!\n";
} else {
    echo "User found: {$foundUser->full_name} ({$foundUser->phone_number})\n";
    $pinCheck = Hash::check($testPin, $foundUser->pin_hash);
    echo "PIN check ({$testPin}): " . var_export($pinCheck, true) . "\n";
    
    if ($pinCheck) {
        echo "LOGIN WOULD SUCCEED!\n";
    } else {
        echo "PIN CHECK FAILED! Hash: {$foundUser->pin_hash}\n";
        echo "Hash info: " . json_encode(password_get_info($foundUser->pin_hash)) . "\n";
    }
}

// Cleanup
$agent->delete();
$agentUser->delete();
echo "\nTest agent cleaned up.\n";
