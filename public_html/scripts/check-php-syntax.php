<?php

// PHP's parser validates source without booting Laravel or reading any environment.
$root = dirname(__DIR__);
$count = 0;
foreach (['app', 'bootstrap', 'config', 'routes', 'platform', 'tests', 'scripts'] as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        $path = str_replace('\\', '/', $file->getPathname());
        if (! str_ends_with($path, '.php') || str_ends_with($path, '.blade.php') || str_contains($path, '/bootstrap/cache/')) {
            continue;
        }
        try {
            token_get_all(file_get_contents($path), TOKEN_PARSE);
            $count++;
        } catch (ParseError $error) {
            fwrite(STDERR, $path . ': ' . $error->getMessage() . "\n");
            exit(1);
        }
    }
}
echo "PHP syntax: {$count} source files passed.\n";
