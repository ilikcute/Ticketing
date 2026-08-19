<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Participant;
use App\Models\User;

$participant = Participant::first();
echo "Testing participant: ID {$participant->id}, Full Name: {$participant->full_name}, BIB Name: {$participant->bib_name}\n";

// Test 1: updateBibName via BibCheckController logic
$request = Illuminate\Http\Request::create('/bib-check/update-name', 'POST', [
    'code' => $participant->pin_code,
    'bib_name' => 'AHMAD PELARI CEPAT',
]);
$controller = new App\Http\Controllers\BibCheckController();
$response = $controller->updateBibName($request);
echo "BibCheckController Response status: " . $response->getStatusCode() . "\n";
echo "Response content: " . $response->getContent() . "\n";

$participant->refresh();
echo "Updated bib_name in DB: {$participant->bib_name}\n";

// Test 2: updateBibName via LoketController logic
$user = User::first();
$loketRequest = Illuminate\Http\Request::create('/loket/update-bib-name', 'POST', [
    'pin_code' => $participant->pin_code,
    'bib_name' => 'BUDI RUNNER PRO',
]);
$loketRequest->setUserResolver(fn() => $user);

$loketController = new App\Http\Controllers\LoketController();
$loketResponse = $loketController->updateBibName($loketRequest);
echo "LoketController Response status: " . $loketResponse->getStatusCode() . "\n";
echo "Response content: " . $loketResponse->getContent() . "\n";

$participant->refresh();
echo "Final bib_name in DB: {$participant->bib_name}\n";
