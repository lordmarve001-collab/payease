<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = App\Models\User::whereHas('roles', function ($q) {
    $q->whereIn('name', ['admin', 'super_admin']);
})->first();
auth()->login($admin);

// Simulate a Livewire round-trip for requestAction
$request = Illuminate\Http\Request::create('/admin/ajo-groups', 'POST', [
    'components' => [['name' => 'admin.ajo-groups', 'state' => []]],
]);
Livewire::component('admin.ajo-groups', App\Livewire\Admin\AjoGroups::class);

$component = App\Livewire\Admin\AjoGroups::mount();
echo "Mount OK\n";

// Check that requestAction method exists
echo "requestAction exists: " . (method_exists($component, 'requestAction') ? 'YES' : 'NO') . "\n";
echo "executeAction exists: " . (method_exists($component, 'executeAction') ? 'YES' : 'NO') . "\n";
echo "viewGroup exists: " . (method_exists($component, 'viewGroup') ? 'YES' : 'NO') . "\n";
echo "closeModal exists: " . (method_exists($component, 'closeModal') ? 'YES' : 'NO') . "\n";

$group = App\Models\AjoGroup::first();
if ($group) {
    echo "First group: {$group->name} (status: {$group->status}, id: {$group->id})\n";
} else {
    echo "No groups found\n";
}

exit(0);
