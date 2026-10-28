<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach(\App\Models\Survei\SesiSurvei::whereNull('token_rt')->get() as $s) {
    $s->update([
        'token_rt' => \Illuminate\Support\Str::random(32),
        'token_masyarakat' => \Illuminate\Support\Str::random(32),
        'token_produsen' => \Illuminate\Support\Str::random(32)
    ]);
}
echo "Done";
