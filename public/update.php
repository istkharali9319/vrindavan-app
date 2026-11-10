<?php

/**
 * Runs deploy tasks from the browser, for hosting without shell access.
 *
 * Set UPDATE_TOKEN=<long random text> in .env, then open:
 *   /update.php?token=<UPDATE_TOKEN>&task=<task>
 *
 * Remove UPDATE_TOKEN from .env (or delete this file) when you are done.
 */

use Dotenv\Dotenv;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

// Read the token straight from .env so it still works when the config is cached.
$envFile = base_path('.env');
$expected = is_file($envFile) ? (Dotenv::parse((string) file_get_contents($envFile))['UPDATE_TOKEN'] ?? '') : '';
$given = (string) ($_GET['token'] ?? '');

if (strlen($expected) < 20 || ! hash_equals($expected, $given)) {
    http_response_code(404);
    exit('Not found');
}

$tasks = [
    'migrate' => ['Run new database migrations', 'migrate', ['--force' => true]],
    'images-preview' => ['Preview image optimization (changes nothing)', 'images:optimize', ['--dry-run' => true]],
    'images' => ['Resize and compress uploaded images', 'images:optimize', []],
    'storage-link' => ['Link public/storage to uploaded files', 'storage:link', []],
    'optimize' => ['Cache config, routes and views', 'optimize', []],
    'clear' => ['Clear all caches', 'optimize:clear', []],
];

$task = (string) ($_GET['task'] ?? '');
$token = htmlspecialchars($given, ENT_QUOTES);

echo '<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><title>Update</title>';
echo '<body style="font-family:system-ui,sans-serif;max-width:860px;margin:24px auto;padding:0 16px">';
echo '<h1>Update tasks</h1><ul>';
foreach ($tasks as $key => [$label]) {
    echo "<li><a href=\"?token={$token}&task={$key}\">".htmlspecialchars($label).'</a></li>';
}
echo '</ul>';

if (isset($tasks[$task])) {
    [$label, $command, $options] = $tasks[$task];

    set_time_limit(0);
    ignore_user_abort(true);

    echo '<h2>'.htmlspecialchars($label).'</h2>';

    try {
        $code = Artisan::call($command, $options);
        $output = Artisan::output();
    } catch (Throwable $e) {
        $code = 1;
        $output = $e->getMessage();
    }

    echo '<pre style="background:#f4f4f4;padding:12px;white-space:pre-wrap">'.htmlspecialchars($output ?: '(no output)').'</pre>';
    echo $code === 0 ? '<p style="color:green">Done.</p>' : '<p style="color:#b00">Failed (exit code '.$code.').</p>';
}
