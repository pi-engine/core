<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 */

namespace Pi\Core\Application\Service;

use phpseclib3\Crypt\AES as CryptAES;
use phpseclib3\Crypt\Rijndael as CryptRijndael;
use phpseclib3\Crypt\Twofish as CryptTwofish;
use phpseclib3\Crypt\Blowfish as CryptBlowfish;
use phpseclib3\Crypt\RC4 as CryptRC4;
use phpseclib3\Crypt\RC2 as CryptRC2;
use phpseclib3\Crypt\TripleDES as CryptTripleDES;
use phpseclib3\Crypt\DES as CryptDES;

/**
 * Encryption service, use phpseclib for encrypt and decrypt
 * more information, source, documents and examples : http://phpseclib.sourceforge.net/
 */
class Encryption implements ServiceInterface
{
    // Set default key
    protected string $key = 'jkWdCv78Frt4SDhzk';

    // Set default iv
    protected string $iv = 'fGyse67d';

    /**
     * Encrypt string
     *
     * @param string $input
     * @param string $type
     * @param array $options
     *
     * @return string $output
     */
    public function process(string $input, string $type = 'encrypt', array $options = []): string
    {
        // Set options
        $options['key']          = $options['key'] ?? $this->key;
        $options['iv']           = $options['iv'] ?? $this->iv;
        $options['method']       = $options['method'] ?? 'Blowfish';
        $options['length']       = $options['length'] ?? '16';
        $options['block_length'] = $options['block_length'] ?? '8';
        $options['mode']         = $options['mode'] ?? 'CBC';

        // Start process
        $output = '';
        switch ($options['method']) {
            case 'AES':
                $output = $this->AES($input, $options, $type);
                break;
            case 'Rijndael':
                $output = $this->Rijndael($input, $options, $type);
                break;
            case 'Twofish':
                $output = $this->Twofish($input, $options, $type);
                break;
            case 'Blowfish':
                $output = $this->Blowfish($input, $options, $type);
                break;
            case 'RC4':
                $output = $this->RC4($input, $options, $type);
                break;
            case 'RC2':
                $output = $this->RC2($input, $options, $type);
                break;
            case 'TripleDES':
                $output = $this->TripleDES($input, $options, $type);
                break;
            case 'DES':
                $output = $this->DES($input, $options, $type);
                break;
        }
        
        return $output;
    }

    /**
     * AES encryption
     *
     * @param string $input
     * @param string $type
     * @param array $options
     *
     * @return string $output
     */
    protected function AES(string $input, array $options, string $type = 'encrypt'): string
    {
        // Encryption
        $cipher = new CryptAES($options['mode']);
        $cipher->setKey($options['key']);
        $cipher->setIV($options['iv']);
        $cipher->setKeyLength($options['length']);
        if ($type == 'decrypt') {
            $output = $cipher->decrypt($input);
        } else {
            $output = $cipher->encrypt($input);
        }

        return $output;
    }

    /**
     * Rijndael encryption
     *
     * @param string $input
     * @param string $type
     * @param array  $options
     *
     * @return string $output
     */
    protected function Rijndael(string $input, array $options, string $type = 'encrypt'): string
    {
        // Encryption
        $cipher = new CryptRijndael($options['mode']);
        $cipher->setKey($options['key']);
        $cipher->setIV($options['iv']);
        $cipher->setKeyLength($options['length']);
        $cipher->setBlockLength($options['block_length']);
        if ($type == 'decrypt') {
            $output = $cipher->decrypt(hex2bin($input));
        } else {
            $output = bin2hex($cipher->encrypt($input));
        }

        return $output;
    }

    /**
     * Twofish encryption
     *
     * @param string $input
     * @param string $type
     * @param array  $options
     *
     * @return string $output
     */
    protected function Twofish(string $input, array $options, string $type = 'encrypt'): string
    {
        // Encryption
        $cipher = new CryptTwofish($options['mode']);
        $cipher->setKey($options['key']);
        $cipher->setIV($options['iv']);
        if ($type == 'decrypt') {
            $output = $cipher->decrypt(hex2bin($input));
        } else {
            $output = bin2hex($cipher->encrypt($input));
        }

        return $output;
    }

    /**
     * Blowfish encryption
     *
     * @param string $input
     * @param string $type
     * @param array  $options
     *
     * @return string $output
     */
    protected function Blowfish(string $input, array $options, string $type = 'encrypt'): string
    {
        // Encryption
        $cipher = new CryptBlowfish($options['mode']);
        $cipher->setKey($options['key']);
        $cipher->setIV($options['iv']);
        if ($type == 'decrypt') {
            $output = $cipher->decrypt(hex2bin($input));
        } else {
            $output = bin2hex($cipher->encrypt($input));
        }

        return $output;
    }

    /**
     * RC4 encryption
     *
     * @param string $input
     * @param string $type
     * @param array  $options
     *
     * @return string $output
     */
    protected function RC4(string $input, array $options, string $type = 'encrypt'): string
    {
        // Encryption
        $cipher = new CryptRC4($options['mode']);
        $cipher->setKey($options['key']);
        $cipher->setIV($options['iv']);
        if ($type == 'decrypt') {
            $output = $cipher->decrypt(hex2bin($input));
        } else {
            $output = bin2hex($cipher->encrypt($input));
        }

        return $output;
    }

    /**
     * RC2 encryption
     *
     * @param string $input
     * @param string $type
     * @param array  $options
     *
     * @return string $output
     */
    protected function RC2(string $input, array $options, string $type = 'encrypt'): string
    {
        // Encryption
        $cipher = new CryptRC2($options['mode']);
        $cipher->setKey($options['key']);
        $cipher->setIV($options['iv']);
        if ($type == 'decrypt') {
            $output = $cipher->decrypt(hex2bin($input));
        } else {
            $output = bin2hex($cipher->encrypt($input));
        }

        return $output;
    }

    /**
     * TripleDES encryption
     *
     * @param string $input
     * @param string $type
     * @param array  $options
     *
     * @return string $output
     */
    protected function TripleDES(string $input, array $options, string $type = 'encrypt'): string
    {
        // Encryption
        $cipher = new CryptTripleDES($options['mode']);
        $cipher->setKey($options['key']);
        $cipher->setIV($options['iv']);
        if ($type == 'decrypt') {
            $output = $cipher->decrypt(hex2bin($input));
        } else {
            $output = bin2hex($cipher->encrypt($input));
        }

        return $output;
    }

    /**
     * DES encryption
     *
     * @param string $input
     * @param string $type
     * @param array  $options
     *
     * @return string $output
     */
    protected function DES(string $input, array $options, string $type = 'encrypt'): string
    {
        // Encryption
        $cipher = new CryptDES($options['mode']);
        $cipher->setKey($options['key']);
        $cipher->setIV($options['iv']);
        if ($type == 'decrypt') {
            $output = $cipher->decrypt(hex2bin($input));
        } else {
            $output = bin2hex($cipher->encrypt($input));
        }

        return $output;
    }
}