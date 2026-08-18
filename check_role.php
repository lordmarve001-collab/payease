<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

$user = User::where('phone_number', '8012345001')->first();

if ($user) {
    echo "User found: " . $user->full_name . "\n";
    echo "User ID: " . $user->id . "\n";
    echo "Roles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
    
    if (!$user->hasRole('customer')) {
        echo "\nUser does NOT have 'customer' role. Assigning...\n";
        $user->assignRole('customer');
        echo "Role assigned successfully!\n";
    } else {
        echo "\nUser already has 'customer' role.\n";
    }
} else {
    echo "User not found\n";
}
