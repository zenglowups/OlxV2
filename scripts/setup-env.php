<?php

// Standalone: runs before Composer is installed. Never overwrites an existing .env.
$root = dirname(__DIR__);
$target = $root.'/.env';
if (file_exists($target)) {
    fwrite(STDOUT, "Existing .env preserved.\n");
    exit(0);
}
$template = file_get_contents($root.'/.env.example');
$values = [
    'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'DB_PASSWORD' => bin2hex(random_bytes(24)),
    'AWS_ACCESS_KEY_ID' => 'local-'.bin2hex(random_bytes(8)),
    'AWS_SECRET_ACCESS_KEY' => bin2hex(random_bytes(32)),
];
foreach ($values as $key => $value) {
    $template = preg_replace('/^'.preg_quote($key, '/').'=$/m', $key.'='.$value, $template);
}
$handle = fopen($target, 'x');
if ($handle === false) {
    throw new RuntimeException('Could not create .env; no existing configuration was changed.');
}
fwrite($handle, $template);
fclose($handle);
chmod($target, 0600);
fwrite(STDOUT, "Created local .env with random credentials. Keep it private and backed up.\n");
