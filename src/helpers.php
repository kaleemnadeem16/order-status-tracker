<?php
/**
 * Small procedural view helpers shared across plain-PHP pages.
 */

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Asset URL with a cache-busting ?v= stamp from the file's mtime, so an edited
 * CSS/JS file is always picked up instead of being served from browser cache.
 */
function asset(string $relativePath): string
{
    $relativePath = ltrim($relativePath, '/');
    $full = __DIR__ . '/../' . $relativePath;
    $version = is_file($full) ? filemtime($full) : null;
    return APP_URL . '/' . $relativePath . ($version ? '?v=' . $version : '');
}

/** Returns null when no duration is set — steps with no expected time show nothing at all. */
function format_duration(?float $value, string $unit): ?string
{
    if ($unit === 'instant' || $value === null || $value == 0) {
        return null;
    }
    $unitLabel = rtrim($unit, 's');
    $n = $value == floor($value) ? (int) $value : $value;
    return $n . ' ' . $unitLabel . ($n == 1 ? '' : 's');
}

function format_date(?string $date): ?string
{
    if (!$date) {
        return null;
    }
    $ts = strtotime($date);
    return $ts ? date('M j, Y', $ts) : null;
}

/** Public tracking URL for a token — pretty path, no .php exposed to customers. */
function public_track_url(string $token): string
{
    return APP_URL . '/track/' . $token;
}
