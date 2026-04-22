<?php

declare(strict_types=1);

/**
 * Composer post-install helper: copies .env.example → .env if .env is absent.
 *
 * Runs automatically after `composer install` via the post-install-cmd hook.
 */

// Resolve project root relative to this script (scripts/ → repo root)
$root = dirname(__DIR__);
$source = $root.'/.env.example';
$dest = $root.'/.env';

if (! file_exists($source)) {
    // Nothing to copy; not an error (e.g. consuming project, not the package root)
    exit(0);
}

if (file_exists($dest)) {
    echo "  .env already exists — skipping copy.\n";
    exit(0);
}

if (copy($source, $dest)) {
    echo "\n";
    echo "  ✓ Copied .env.example → .env\n";
    echo "  Open .env and set DISCORD_TOKEN to your bot token before running your bot.\n";
    echo "  Get a token at: https://discord.com/developers/applications\n";
    echo "\n";
} else {
    fwrite(STDERR, "  ✗ Could not copy .env.example → .env. Please do it manually.\n");
    exit(1);
}
