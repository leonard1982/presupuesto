<?php
/**
 * Proyecto PRESUPUESTO - Servicio de autenticacion con compatibilidad MD5.
 */

namespace App\Modules\Auth\Services;

use App\Core\Logger;
use App\Core\SessionManager;
use App\Modules\Auth\Repositories\UserRepository;

class AuthService
{
    private $userRepository;
    private $logger;

    public function __construct(UserRepository $userRepository, Logger $logger)
    {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    public function attemptLogin($username, $plainPassword)
    {
        $user = $this->userRepository->findByLogin($username);
        if (!$user) {
            return false;
        }

        $isActive = isset($user['active']) && strtoupper((string) $user['active']) === 'Y';
        if (!$isActive) {
            return false;
        }

        if ($this->verifyModernHash($user, $plainPassword)) {
            $this->registerAuthenticatedSession($user);
            return true;
        }

        if ($this->verifyLegacyMd5($user, $plainPassword)) {
            $this->migrateLegacyPasswordIfPossible($username, $plainPassword);
            $this->registerAuthenticatedSession($user);
            return true;
        }

        return false;
    }

    public function isAuthenticated()
    {
        return isset($_SESSION['authenticated_user']) && is_array($_SESSION['authenticated_user']);
    }

    public function getAuthenticatedUser()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return $_SESSION['authenticated_user'];
    }

    public function logout()
    {
        SessionManager::destroy();
    }

    private function verifyModernHash(array $user, $plainPassword)
    {
        if (!isset($user['password_hash'])) {
            return false;
        }

        $storedModernHash = (string) $user['password_hash'];
        if ($storedModernHash === '') {
            return false;
        }

        return password_verify($plainPassword, $storedModernHash);
    }

    private function verifyLegacyMd5(array $user, $plainPassword)
    {
        if (!isset($user['pswd'])) {
            return false;
        }

        $legacyHash = strtolower(trim((string) $user['pswd']));
        if (strlen($legacyHash) !== 32 || !ctype_xdigit($legacyHash)) {
            return false;
        }

        $providedHash = md5($plainPassword);
        return hash_equals($legacyHash, strtolower($providedHash));
    }

    private function migrateLegacyPasswordIfPossible($username, $plainPassword)
    {
        if (!$this->userRepository->supportsModernHash()) {
            $this->logger->warning('app', 'No se pudo migrar hash por falta de columnas de migracion.', array(
                'username' => $username,
            ));
            return;
        }

        $newHash = password_hash($plainPassword, PASSWORD_BCRYPT);
        if (!is_string($newHash) || $newHash === '') {
            $this->logger->error('app', 'No fue posible generar password_hash.', array('username' => $username));
            return;
        }

        $migrated = $this->userRepository->updateModernHash($username, $newHash);
        if ($migrated) {
            $this->logger->info('app', 'Contrasena migrada de MD5 a hash moderno.', array('username' => $username));
        }
    }

    private function registerAuthenticatedSession(array $user)
    {
        $_SESSION['authenticated_user'] = array(
            'login' => isset($user['login']) ? (string) $user['login'] : '',
            'name' => isset($user['name']) ? (string) $user['name'] : '',
            'email' => isset($user['email']) ? (string) $user['email'] : '',
            'role' => isset($user['role']) ? (string) $user['role'] : '',
            'priv_admin' => isset($user['priv_admin']) ? (string) $user['priv_admin'] : '',
        );
    }
}
