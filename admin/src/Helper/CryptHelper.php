<?php
/**
 * @package     com_content_api_grabber
 * @subpackage  Helper
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Nickpsal\Component\ContentApiGrabber\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

/**
 * Symmetric encryption for secrets (API tokens) stored at rest in the database.
 *
 * Uses libsodium's authenticated secretbox with a key derived from the site
 * `secret` (from configuration.php). A token is therefore unreadable from the
 * database alone — an attacker also needs the site secret to decrypt it.
 */
class CryptHelper
{
    /**
     * Marker prefix so we can tell encrypted values apart from legacy/plain ones.
     */
    private const PREFIX = 'jag1:';

    /**
     * Derive a 32-byte key from the site secret.
     *
     * @return  string
     *
     * @throws  \RuntimeException  When the site secret is empty.
     */
    private static function key(): string
    {
        $secret = (string) Factory::getApplication()->get('secret', '');

        if ($secret === '') {
            throw new \RuntimeException('Cannot encrypt: the site secret is empty.');
        }

        return sodium_crypto_generichash($secret, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }

    /**
     * Encrypt a plaintext string. Returns '' for empty input.
     *
     * @param   string  $plain  The plaintext.
     *
     * @return  string  Storable, prefixed, base64 ciphertext.
     */
    public static function encrypt(string $plain): string
    {
        if ($plain === '') {
            return '';
        }

        $nonce  = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plain, $nonce, self::key());

        return self::PREFIX . base64_encode($nonce . $cipher);
    }

    /**
     * Decrypt a value produced by encrypt(). Returns '' if it cannot be decrypted.
     *
     * @param   string  $stored  The stored value.
     *
     * @return  string
     */
    public static function decrypt(string $stored): string
    {
        if ($stored === '') {
            return '';
        }

        if (!str_starts_with($stored, self::PREFIX)) {
            // Not in our format (e.g. legacy plaintext) — return as-is.
            return $stored;
        }

        $raw = base64_decode(substr($stored, \strlen(self::PREFIX)), true);

        if ($raw === false || \strlen($raw) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES) {
            return '';
        }

        $nonce  = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $plain = sodium_crypto_secretbox_open($cipher, $nonce, self::key());

        return $plain === false ? '' : $plain;
    }

    /**
     * Whether a stored value is already in our encrypted format.
     *
     * @param   string  $value  The value.
     *
     * @return  boolean
     */
    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    /**
     * Mask a token for display (show only the last 4 chars).
     *
     * @param   string  $token  The plaintext token.
     *
     * @return  string
     */
    public static function mask(string $token): string
    {
        $len = \strlen($token);

        if ($len === 0) {
            return '';
        }

        return str_repeat('•', max(0, min(8, $len - 4))) . substr($token, -4);
    }
}
