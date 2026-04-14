<?php

use Illuminate\Support\Facades\Hash;
use App\Models\User;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = User::where('email', 'krina@admin.com')->first();

if (!$u) {
    echo "ERROR: No user found with that email.\n";
    exit(1);
}

$u->password = Illuminate\Support\Facades\Hash::make('Admin1234!');
$u->save();

echo "Done.\n";
echo "Email:  " . $u->email  . "\n";
echo "Role:   " . $u->role   . "\n";
echo "Status: " . $u->status . "\n";
