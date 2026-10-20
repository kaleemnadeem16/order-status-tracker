<?php
namespace App;

/** Single-host fixed window throttle, keyed by actual peer IP, not forwarded headers. */
class RateLimit
{
    public static function enforce(string $peer): void
    {
        $path = sys_get_temp_dir() . '/tracker-rate-' . hash('sha256', $peer);
        $file = fopen($path, 'c+');
        if (!$file || !flock($file, LOCK_EX)) {
            http_response_code(503);
            exit('Tracking temporarily unavailable.');
        }
        $state = json_decode(stream_get_contents($file), true) ?: [];
        $now = time();
        if (($state['until'] ?? 0) <= $now) { $state = ['until' => $now + 60, 'count' => 0]; }
        $allowed = ++$state['count'] <= 60;
        rewind($file);
        ftruncate($file, 0);
        fwrite($file, json_encode($state));
        flock($file, LOCK_UN);
        fclose($file);
        if (!$allowed) {
            header('Retry-After: ' . max(1, $state['until'] - $now));
            http_response_code(429);
            exit('Too many requests. Try again shortly.');
        }
    }
}
