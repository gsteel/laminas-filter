<?php

/**
 * @see       https://github.com/laminas/laminas-filter for the canonical source repository
 * @copyright https://github.com/laminas/laminas-filter/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-filter/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Filter\Encrypt;

use Laminas\Crypt\BlockCipher as CryptBlockCipher;
use Laminas\Crypt\Exception as CryptException;
use Laminas\Crypt\Symmetric\Exception as SymmetricException;
use Laminas\Filter\Compress;
use Laminas\Filter\Decompress;
use Laminas\Filter\Exception;
use Laminas\Stdlib\ArrayUtils;
use Traversable;

/**
 * Encryption adapter for Laminas\Crypt\BlockCipher
 *
 * @category   Laminas
 * @package    Laminas_Filter
 */
class BlockCipher implements EncryptionAlgorithmInterface
{
    /**
     * Definitions for encryption
     * array(
     *     'key'           => encryption key string
     *     'key_iteration' => the number of iterations for the PBKDF2 key generation
     *     'algorithm      => cipher algorithm to use
     *     'hash'          => algorithm to use for the authentication
     *     'iv'            => initialization vector
     * )
     */
    protected $encryption = array(
        'key'                 => 'Laminas',
        'key_iteration'       => 5000,
        'algorithm'           => 'aes',
        'hash'                => 'sha256',
        'vector'              => null,
    );

    /**
     * BlockCipher
     *
     * @var BlockCipher
     */
    protected $blockCipher;

    /**
     * Internal compression
     *
     * @var array
     */
    protected $compression;

    /**
     * Class constructor
     *
     * @param  string|array|\Traversable $options Encryption Options
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($options)
    {
        try {
            $this->blockCipher = CryptBlockCipher::factory('mcrypt', $this->encryption);
        } catch (SymmetricException\RuntimeException $e) {
            throw new Exception\RuntimeException('The BlockCipher cannot be used without the Mcrypt extension');
        }

        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        } elseif (is_string($options)) {
            $options = array('key' => $options);
        } elseif (!is_array($options)) {
            throw new Exception\InvalidArgumentException('Invalid options argument provided to filter');
        }

        if (array_key_exists('compression', $options)) {
            $this->setCompression($options['compression']);
            unset($options['compress']);
        }

        $this->setEncryption($options);
    }

    /**
     * Returns the set encryption options
     *
     * @return array
     */
    public function getEncryption()
    {
        return $this->encryption;
    }

    /**
     * Sets new encryption options
     *
     * @param  string|array $options Encryption options
     * @return BlockCipher
     * @throws Exception\InvalidArgumentException
     */
    public function setEncryption($options)
    {
        if (is_string($options)) {
            $this->blockCipher->setKey($options);
            $this->encryption['key'] = $options;
            return $this;
        }

        if (!is_array($options)) {
            throw new Exception\InvalidArgumentException('Invalid options argument provided to filter');
        }

        $options = $options + $this->encryption;

        if (isset($options['key'])) {
            $this->blockCipher->setKey($options['key']);
        }

        if (isset($options['algorithm'])) {
            try {
                $this->blockCipher->setCipherAlgorithm($options['algorithm']);
            } catch (CryptException\InvalidArgumentException $e) {
                throw new Exception\InvalidArgumentException("The algorithm '{$options['algorithm']}' is not supported");
            }
        }

        if (isset($options['hash'])) {
            try {
                $this->blockCipher->setHashAlgorithm($options['hash']);
            } catch (CryptException\InvalidArgumentException $e) {
                throw new Exception\InvalidArgumentException("The algorithm '{$options['hash']}' is not supported");
            }
        }

        if (isset($options['vector'])) {
            $this->setVector($options['vector']);
        }

        if (isset($options['key_iteration'])) {
            $this->blockCipher->setKeyIteration($options['key_iteration']);
        }

        $this->encryption = $options;

        return $this;
    }

    /**
     * Returns the initialization vector
     *
     * @return string
     */
    public function getVector()
    {
        return $this->encryption['vector'];
    }

    /**
     * Set the inizialization vector
     *
     * @param  string $vector
     * @return BlockCipher
     * @throws Exception\InvalidArgumentException
     */
    public function setVector($vector)
    {
        try {
            $this->blockCipher->setSalt($vector);
        } catch (CryptException\InvalidArgumentException $e) {
            throw new Exception\InvalidArgumentException($e->getMessage());
        }
        $this->encryption['vector'] = $vector;
        return $this;
    }

    /**
     * Returns the compression
     *
     * @return array
     */
    public function getCompression()
    {
        return $this->compression;
    }

    /**
     * Sets a internal compression for values to encrypt
     *
     * @param  string|array $compression
     * @return BlockCipher
     */
    public function setCompression($compression)
    {
        if (is_string($this->compression)) {
            $compression = array('adapter' => $compression);
        }

        $this->compression = $compression;
        return $this;
    }

    /**
     * Defined by Laminas\Filter\FilterInterface
     *
     * Encrypts $value with the defined settings
     *
     * @param  string $value The content to encrypt
     * @return string The encrypted content
     */
    public function encrypt($value)
    {
        // compress prior to encryption
        if (!empty($this->compression)) {
            $compress = new Compress($this->compression);
            $value    = $compress($value);
        }

        try {
            $encrypted = $this->blockCipher->encrypt($value);
        } catch (CryptException\InvalidArgumentException $e) {
            throw new Exception\InvalidArgumentException($e->getMessage());
        } catch (SymmetricException\InvalidArgumentException $e) {
            throw new Exception\InvalidArgumentException($e->getMessage());
        }
        return $encrypted;
    }

    /**
     * Defined by Laminas\Filter\FilterInterface
     *
     * Decrypts $value with the defined settings
     *
     * @param  string $value Content to decrypt
     * @return string The decrypted content
     */
    public function decrypt($value)
    {
        $decrypted = $this->blockCipher->decrypt($value);

        // decompress after decryption
        if (!empty($this->compression)) {
            $decompress = new Decompress($this->compression);
            $decrypted  = $decompress($decrypted);
        }

        return $decrypted;
    }

    /**
     * Returns the adapter name
     *
     * @return string
     */
    public function toString()
    {
        return 'BlockCipher';
    }

}
