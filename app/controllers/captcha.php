<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$mode = ($_GET['mode'] ?? '') === 'signup' ? 'signup' : 'login';
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 6; $i++) $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
$_SESSION['captcha'][$mode] = ['hash' => hash('sha256', $code), 'expires' => time() + 300];
header('Content-Type: image/png');
header('Cache-Control: no-store, private');
$image = imagecreatetruecolor(220, 64);
$background = imagecolorallocate($image, 238, 241, 247);
$ink = imagecolorallocate($image, 32, 47, 72);
$noise = imagecolorallocate($image, 160, 175, 195);
imagefill($image, 0, 0, $background);
for ($i = 0; $i < 12; $i++) imageline($image, random_int(0, 219), random_int(0, 63), random_int(0, 219), random_int(0, 63), $noise);
for ($i = 0; $i < 6; $i++) imagestring($image, 5, 20 + $i * 30, random_int(15, 30), $code[$i], $ink);
imagepng($image);
imagedestroy($image);
