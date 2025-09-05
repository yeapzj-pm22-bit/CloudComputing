<?php
// helpers/SessionHelper.php

class SessionHelper {
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function isLoggedIn(): bool {
        self::start();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            header('Location: /auth/login.php');
            exit;
        }
    }
    
    public static function requireAdmin(): bool {
        self::requireLogin();
        if ($_SESSION['account_type'] !== 'Admin') {
            return false;
        }
        return true;
    }
    
    public static function getUserId(): ?int {
        self::start();
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function getAccountType(): ?string {
        self::start();
        return $_SESSION['account_type'] ?? null;
    }
    
    public static function setFlashMessage(string $message, string $type = 'success'): void {
        self::start();
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    
    public static function getFlashMessage(): ?array {
        self::start();
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            $type = $_SESSION['flash_type'] ?? 'success';
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            return ['message' => $message, 'type' => $type];
        }
        return null;
    }
    
    public static function destroy(): void {
        self::start();
        session_destroy();
        session_unset();
    }
}
?>