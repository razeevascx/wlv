<?php
require_once dirname(__DIR__) . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit;
}

$json = ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json';
function account_success(bool $json): never
{
    if ($json) {
        header('Content-Type: application/json');
        exit(json_encode(['redirect' => 'collections.php']));
    }
    redirect('collections.php');
}
if (isset($_SESSION['user_id'])) account_success($json);

$email = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
    $password = $_POST['password'] ?? '';
    $id = null;
    $mode = $signup ? 'signup' : 'login';
    $challenge = $_SESSION['captcha'][$mode] ?? [];
    unset($_SESSION['captcha'][$mode]);
    $answer = is_string($_POST['captcha'] ?? null) ? strtoupper(trim($_POST['captcha'])) : '';
    if (($challenge['expires'] ?? 0) < time() || !hash_equals($challenge['hash'] ?? '', hash('sha256', $answer))) {
        $error = 'Enter the characters shown in the new security image.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $error = 'Enter a valid email address.';
    } elseif (!is_string($password) || str_contains($password, "\0")) {
        $error = $signup ? 'Choose a different password.' : 'Email or password is incorrect.';
    } elseif (strlen($password) > 72) {
        $error = $signup ? 'That password is too long. Try a shorter one.' : 'Email or password is incorrect.';
    } elseif ($signup && mb_strlen($password, 'UTF-8') < 8) {
        $error = 'Use at least 8 characters.';
    } elseif ($signup) {
        try {
            $mysqli->execute_query('INSERT INTO users (email, password_hash) VALUES (?, ?)', [$email, password_hash($password, PASSWORD_DEFAULT)]);
            $id = $mysqli->insert_id;
        } catch (mysqli_sql_exception $exception) {
            if ($exception->getCode() !== 1062) throw $exception;
            $error = 'An account already uses that email.';
        }
    } else {
        $user = $mysqli->execute_query('SELECT id, password_hash FROM users WHERE email = ?', [$email])->fetch_assoc();
        if ($user && password_verify($password, $user['password_hash'])) {
            $id = $user['id'];
        } else {
            $error = 'Email or password is incorrect.';
        }
    }
    if ($id) {
        session_regenerate_id(true);
        $_SESSION = ['user_id' => (int) $id];
        account_success($json);
    }
    if ($json) {
        http_response_code(422);
        header('Content-Type: application/json');
        exit(json_encode(['error' => $error]));
    }
}
$_SESSION['account_error'] = ['signup' => $signup, 'email' => $email, 'error' => $error];
redirect('index.php?auth=' . ($signup ? 'signup' : 'login'));
