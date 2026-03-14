<?php
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'login';
$user = $input['username'] ?? '';
$pass = $input['password'] ?? '';

$pythonScript = __DIR__ . '/../../src/Graph/neo_manager.py';
$cmd = "python " . escapeshellarg($pythonScript) . " " . escapeshellarg($action) . " " . escapeshellarg($user) . " " . escapeshellarg($pass);
$output = shell_exec($cmd);

$result = json_decode($output, true);

if ($result && $result['status'] === 'success' && $action === 'login') {
    $_SESSION['user'] = $result['username'];
    $_SESSION['role'] = $result['role']; // On stocke 'ADMIN' ou 'USER'
}
echo $output;