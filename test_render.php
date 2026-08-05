<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$cv = App\Models\CV::with('template')->find(3);
try {
    echo view('cvs.pdf', ['cv' => $cv])->render();
} catch (Throwable $e) {
    echo "EXCEPTION: " . get_class($e) . " - " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
