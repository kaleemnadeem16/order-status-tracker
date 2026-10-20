<?php
/** Run only against the disposable local Compose database. Never production. */
require __DIR__ . '/bootstrap.php';
if (getenv('APP_ENV') !== 'local') { throw new RuntimeException('Local environment required'); }
$cookies = [];
function request(string $method, string $path, ?string $body = null, array $headers = []): array {
    global $cookies;
    $headers[] = 'Host: localhost:8092';
    if ($cookies) { $headers[] = 'Cookie: ' . implode('; ', array_map(fn($k,$v) => "$k=$v", array_keys($cookies), $cookies)); }
    $context = stream_context_create(['http' => ['method' => $method, 'header' => implode("\r\n", $headers),
        'content' => $body ?? '', 'ignore_errors' => true, 'follow_location' => 0]]);
    $content = file_get_contents('http://127.0.0.1' . $path, false, $context);
    foreach ($http_response_header as $header) {
        if (preg_match('/^Set-Cookie: ([^=]+)=([^;]*)/i', $header, $m)) { $cookies[$m[1]] = $m[2]; }
    }
    preg_match('/HTTP\/\S+ (\d+)/', $http_response_header[0], $m);
    return [(int) $m[1], $content, $http_response_header];
}
function check(bool $ok, string $label): void { if (!$ok) { throw new RuntimeException($label); } echo "PASS: $label\n"; }
function csrf(string $html): string { preg_match('/name="csrf-token" content="([^"]+)"/', $html, $m); return $m[1] ?? ''; }
$email = 'smoke-' . bin2hex(random_bytes(6)) . '@example.com';
$output = shell_exec(PHP_BINARY . ' scripts/create-admin.php --email=' . escapeshellarg($email));
preg_match('/Temporary password: (\S+)/', $output, $m);
$password = $m[1] ?? '';
check($password !== '', 'CLI admin creation');
try {
    [$status,$html] = request('GET','/login.php');
    check($status === 200, 'login page');
    $token = csrf($html);
    [$status] = request('POST','/login.php',http_build_query(['email'=>$email,'password'=>$password,'csrf_token'=>$token]),['Content-Type: application/x-www-form-urlencoded']);
    check($status === 302, 'login using generated admin');
    [$status,$html] = request('GET','/dashboard.php');
    check($status === 200 && str_contains($html,'Orders'), 'authenticated dashboard');
    $token = csrf($html);
    [$status] = request('POST','/api/orders.php','{}',['Content-Type: application/json']);
    check($status === 403, 'missing CSRF rejected');
    $payload = json_encode(['order_number'=>'DEMO-'.bin2hex(random_bytes(6)), 'start_date'=>'2026-10-21',
        'steps'=>[['name'=>'Received', 'description'=>'<script>alert(1)</script>'],['name'=>'Shipped']]]);
    [$status,$body] = request('POST','/api/orders.php',$payload,['Content-Type: application/json','X-CSRF-Token: '.$token]);
    check($status === 201, 'order created through API');
    $order = json_decode($body,true);
    [$status,$html,$headers] = request('GET','/track/'.$order['token']);
    check($status === 200 && str_contains($html,'Received'), 'public tracking link');
    check(!str_contains($html, '<script>alert(1)</script>'), 'timeline HTML escaped');
    check((bool) preg_grep('/^Referrer-Policy: no-referrer/i',$headers), 'tracking referrer protection');
    [$status,$body] = request('POST','/api/link.php',json_encode(['order_id'=>$order['id']]),['Content-Type: application/json','X-CSRF-Token: '.$token]);
    check($status === 200, 'regenerate token');
    [$status] = request('GET','/track/'.$order['token']);
    check($status === 404, 'old tracking link invalidated');
    foreach (['/config/config.php','/database/schema.sql','/tests/http-smoke.php','/composer.json','/.git/config'] as $path) {
        [$status] = request('GET',$path); check($status === 403, 'internal file blocked: '.$path);
    }
    $adminCookies = $cookies;
    $cookies = [];
    $staffEmail = 'staff-' . bin2hex(random_bytes(6)) . '@example.com';
    $staffPassword = bin2hex(random_bytes(18));
    db()->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)')
        ->execute(['Demo staff', $staffEmail, password_hash($staffPassword, PASSWORD_DEFAULT), 'staff']);
    try {
        [$status,$html] = request('GET','/login.php');
        [$status] = request('POST','/login.php',http_build_query(['email'=>$staffEmail,'password'=>$staffPassword,'csrf_token'=>csrf($html)]),['Content-Type: application/x-www-form-urlencoded']);
        check($status === 302, 'staff login');
        [$status,$html] = request('GET','/dashboard.php');
        $staffToken = csrf($html);
        [$status] = request('GET','/api/orders.php?id='.$order['id']);
        check($status === 404, 'staff cannot read another owners order');
        [$status] = request('POST','/api/link.php',json_encode(['order_id'=>$order['id']]),['Content-Type: application/json','X-CSRF-Token: '.$staffToken]);
        check($status === 404, 'staff cannot regenerate another owners token');
        [$status] = request('GET','/api/users.php');
        check($status === 403, 'staff cannot access user management');
    } finally {
        db()->prepare('DELETE FROM users WHERE email=?')->execute([$staffEmail]);
        $cookies = $adminCookies;
    }
    [$status] = request('GET','/logout.php'); check($status === 403, 'GET logout rejected');
    [$status] = request('POST','/logout.php',http_build_query(['csrf_token'=>$token]),['Content-Type: application/x-www-form-urlencoded']);
    check($status === 302, 'CSRF protected logout');
    [$status] = request('GET','/api/orders.php'); check($status === 401, 'unauthenticated API rejected');
    for ($i=0;$i<61;$i++) { [$status] = request('GET','/track/invalid'); }
    check($status === 429, 'public tracking rate limit');
} finally { db()->prepare('DELETE FROM users WHERE email=?')->execute([$email]); }
