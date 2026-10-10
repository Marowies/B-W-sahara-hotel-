<?php
// Run BEFORE deploying the removal of public/robots.txt. No Laravel bootstrap or database access.
require dirname(__DIR__) . '/platform/packages/theme/src/Supports/RobotsTxt.php';
if ($argc !== 4) {
    fwrite(STDERR, "Usage: php scripts/migrate-robots.php LEGACY_FILE STORAGE_FILE BACKUP_FILE\n");
    exit(2);
}
try {
    Botble\Theme\Supports\RobotsTxt::migrateLegacy($argv[1], $argv[2], $argv[3]);
    echo "Rules backed up and migrated; static override retired. Verify /robots.txt through HTTP.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
