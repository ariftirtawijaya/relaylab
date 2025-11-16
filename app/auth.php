<?php
session_start();

function login_user(array $u): void
{
    $_SESSION['uid'] = $u['id'];
    $_SESSION['role'] = $u['role'];
    $_SESSION['name'] = $u['name'];
}
function logout_user(): void
{
    session_destroy();
}
function me_id(): ?int
{
    return $_SESSION['uid'] ?? null;
}
function me_role(): ?string
{
    return $_SESSION['role'] ?? null;
}

function require_login(): void
{
    if (!me_id()) {
        header('Location: /login.php');
        exit;
    }
}
function require_role(string $role): void
{
    require_login();
    if (me_role() !== $role) {
        http_response_code(403);
        echo "Forbidden";
        exit;
    }
}
