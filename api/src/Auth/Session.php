<?php

declare(strict_types=1);

namespace Prooq\Api\Auth;

use Prooq\Api\Db\Connection;

/**
 * Sesión PHP minimal para admin. Wrappers que centralizan la config de
 * session_start para que sea consistente (cookies httponly + same-site).
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        // `secure` solo bajo HTTPS: en prod (api.prooq.com con SSL) la cookie es
        // segura; en dev local (http://localhost) debe poder setearse igualmente.
        $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
        session_set_cookie_params([
            'lifetime' => 8 * 3600, // 8 horas
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('prooq_admin');
        session_start();
    }

    public static function login(int $userId, string $username, string $role): void
    {
        self::start();
        session_regenerate_id(true); // previene session fixation
        $_SESSION['admin_user_id'] = $userId;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_role'] = $role;
        $_SESSION['admin_login_at'] = time();

        try {
            Connection::get()
                ->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?')
                ->execute([$userId]);
        } catch (\Throwable $e) {
            error_log('Session::login last_login update failed: ' . $e->getMessage());
        }
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function currentUserId(): ?int
    {
        self::start();
        return isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : null;
    }

    public static function isAuthenticated(): bool
    {
        return self::currentUserId() !== null;
    }
}
