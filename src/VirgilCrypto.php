<?php
/**
 * Copyright (C) 2015-2019 Virgil Security Inc.
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *     (1) Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *     (2) Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *     (3) Neither the name of the copyright holder nor the names of its
 *     contributors may be used to endorse or promote products derived from
 *     this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ''AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
 * STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * Lead Maintainer: Virgil Security Inc. <support@virgilsecurity.com>
 */

namespace Virgil\CryptoImpl\VirgilCrypto;

use Virgil\CryptoImpl\Exceptions\VirgilCryptoException;
use Virgil\CryptoImpl\HashAlgorithm;
use Virgil\CryptoImpl\KeyPairType;
use Virgil\CryptoImpl\StreamInput;
use Virgil\CryptoImpl\VirgilCryptoError;
use Virgil\CryptoImpl\VirgilKeyPair;
use Virgil\CryptoImpl\VirgilPrivateKey;
use Virgil\CryptoImpl\VirgilPublicKey;
use VirgilCrypto\Foundation\AlgId;
use VirgilCrypto\Foundation\CtrDrbg;
use \Exception;
use VirgilCrypto\Foundation\KeyMaterialRng;
use VirgilCrypto\Foundation\KeyProvider;
use VirgilCrypto\Foundation\PrivateKey;
use VirgilCrypto\Foundation\PublicKey;
use VirgilCrypto\Foundation\Random;
use VirgilCrypto\Foundation\Sha224;
use VirgilCrypto\Foundation\Sha256;
use VirgilCrypto\Foundation\Sha384;
use VirgilCrypto\Foundation\Sha512;
use VirgilCrypto\Foundation\Signer;
use VirgilCrypto\Foundation\Verifier;

/**
 * Wrapper for cryptographic operations.
 * Class provides a cryptographic operations in applications, such as hashing,
 * signature generation and verification, and encryption and decryption
 * Class VirgilCrypto
 *
 * @package Virgil\CryptoImpl
 */
class VirgilCrypto
{
    /**
     * @var KeyPairType
     */
    private $defaultKeyType;

    /**
     * @var bool
     */
    private $useSHA256Fingerprints;

    /**
     * @var CtrDrbg
     */
    private $rng;

    /**
     * @var int
     */
    private $chunkSize = 1024;

    /**
     * VirgilCrypto constructor.
     *
     * @param KeyPairType|null $defaultKeyType
     * @param bool $useSHA256Fingerprints
     *
     * @throws VirgilCryptoException
     */
    public function __construct(KeyPairType $defaultKeyType = null, bool $useSHA256Fingerprints = false) {
        $this->defaultKeyType = is_null($defaultKeyType) ? KeyPairType::ED25519() : $defaultKeyType;
        $this->useSHA256Fingerprints = $useSHA256Fingerprints;

        try {
            $rng = new CtrDrbg();
            $rng->setupDefaults();
        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage());
        }

        $this->rng = $rng;
    }

    /// Key Generation --->

    /**
     * @param PublicKey $publicKey
     *
     * @return string
     * @throws VirgilCryptoException
     */
    private function computePublicKeyIdentifier(PublicKey $publicKey): string
    {
        try {
            $publicKeyData = $this->exportInternalPublicKey($publicKey);

            $res = $this->computeHash($publicKeyData, HashAlgorithm::SHA256());

            if (!$this->useSHA256Fingerprints) {
                $res = substr($res, 0, 8);
            }

            return $res;

        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Generates KeyPair of default type using seed
     *
     * @param string $seed
     *
     * @return VirgilKeyPair
     * @throws VirgilCryptoException
     */
    public function generateKeyPairUsingSeed(string $seed): VirgilKeyPair
    {
        try {
            if (KeyMaterialRng::KEY_MATERIAL_LEN_MIN > strlen($seed) | KeyMaterialRng::KEY_MATERIAL_LEN_MAX < strlen($seed))
                throw new VirgilCryptoException(VirgilCryptoError::INVALID_SEED_SIZE());

            $seedRng = new KeyMaterialRng();
            $seedRng->resetKeyMaterial($seed);

            return $this->generateKeyPair($this->defaultKeyType, $seedRng);
        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param KeyPairType|null $type
     * @param Random|null $rng
     *
     * @return VirgilKeyPair
     * @throws VirgilCryptoException
     */
    public function generateKeyPair(KeyPairType $type = null, Random $rng = null): VirgilKeyPair
    {
        try {
            $keyProvider = new KeyProvider();

            if(!$type)
                $type = $this->defaultKeyType;

            $bitLen = $type->getRsaBitLen($type);

            if($bitLen)
                $keyProvider->setRsaParams($bitLen);

            if(!$rng)
                $rng = $this->rng;

            $keyProvider->useRandom($rng);
            $keyProvider->setupDefaults();

            $algId = $type->getAlgId($type);

            $privateKey = $keyProvider->generatePrivateKey($algId);
            $publicKey = $privateKey->extractPublicKey();
            $keyId = $this->computePublicKeyIdentifier($publicKey);

            $virgilPrivateKey = new VirgilPrivateKey($keyId, $privateKey, $type);
            $virgilPublicKey = new VirgilPublicKey($keyId, $publicKey, $type);

            return new VirgilKeyPair($virgilPrivateKey, $virgilPublicKey);

        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage(), $e->getCode());
        }
    }

    /// <--- Key Generation

    /// Signatures --->

    /**
     *
     * Generates digital signature of data using private key
     *
     * - Note: Returned value contains only digital signature, not data itself.
     *
     * - Note: Data inside this function is guaranteed to be hashed with SHA512 at least one time.
     *   It's secure to pass raw data here.
     *
     * - Note: Verification algorithm depends on PrivateKey type. Default: EdDSA for ed25519 key
     *
     * @param string $data
     * @param VirgilPrivateKey $virgilPrivateKey
     *
     * @return string
     * @throws VirgilCryptoException
     */
    public function generateSignature(string $data, VirgilPrivateKey $virgilPrivateKey): string
    {
        try {
            $signer = new Signer();
            $signer->useRandom($this->rnd);
            $signer->useHash(new Sha512());

            $signer->reset();
            $signer->appendData($data);

            return $signer->sign($virgilPrivateKey->getPrivateKey());
        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage());
        }
    }

    /**
     * Verifies digital signature of data
     *
     * - Note: Verification algorithm depends on PublicKey type. Default: EdDSA for ed25519 key
     *
     * @param string $signature
     * @param string $data
     * @param VirgilPublicKey $virgilPublicKey
     *
     * @return bool
     * @throws VirgilCryptoException
     */
    public static function verifySignature(string $signature, string $data, VirgilPublicKey $virgilPublicKey): bool
    {
        try {
            $verifier = new Verifier();
            $verifier->reset($signature);
            $verifier->appendData($data);

            return $verifier->verify($virgilPublicKey->getPublicKey());
        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage());
        }
    }

    /**
     * Generates digital signature of data stream using private key
     *
     * - Note: Returned value contains only digital signature, not data itself.
     *
     * - Note: Data inside this function is guaranteed to be hashed with SHA512 at least one time.
     *         It's secure to pass raw data here.
     *
     * @param StreamInput $streamInput
     * @param VirgilPrivateKey $virgilPrivateKey
     *
     * @return void
     * @throws VirgilCryptoException
     */
    public function generateStreamSignature(StreamInput $streamInput, VirgilPrivateKey $virgilPrivateKey)
    {
        try {
            $signer = new Signer();

            $signer->useRandom($this->rnd);
            $signer->useHash(new Sha512());

            $signer->reset();

            // TODO!
            // $data = $streamUtils->forEachChunk($streamInput, $streamSize);
            // $signer->appendData($data);

            $signer->sign($virgilPrivateKey->getPrivateKey());

        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Verifies digital signature of data stream
     *
     * - Note: Verification algorithm depends on PublicKey type. Default: EdDSA
     *
     * @param string $signature
     * @param StreamInput $streamInput
     * @param VirgilPublicKey $virgilPublicKey
     *
     * @return bool
     * @throws VirgilCryptoException
     */
    public static function verifyStreamSignature(string $signature, StreamInput $streamInput, VirgilPublicKey
    $virgilPublicKey): bool
    {
        try {
            $verifier = new Verifier();

            $verifier->reset($signature);

            // TODO!
            // $data = $streamUtils->forEachChunk($streamInput, $streamSize);
            // $signer->appendData($data);

            return $verifier->verify($virgilPublicKey->getPublicKey());

        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage(), $e->getCode());
        }
    }

    /// <--- Signatures

    /// Random --->

    /**
     * @param int $size
     *
     * @return string
     * @throws VirgilCryptoException
     */
    public function generateRandomData(int $size): string
    {
        try {
            return $this->rnd->random($size);
        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage());
        }
    }

    /// <--- Random

    /**
     * Computes hash
     *
     * @param string $data
     * @param HashAlgorithm $algorithm
     *
     * @return null|string
     */
    public function computeHash(string $data, HashAlgorithm $algorithm): ?string
    {
        switch ($algorithm) {
            case $algorithm::SHA224():
                $hash = new Sha224();
                break;
            case $algorithm::SHA256():
                $hash = new Sha256();
                break;
            case $algorithm::SHA384():
                $hash = new Sha384();
                break;
            case $algorithm::SHA512():
                $hash = new Sha512();
                break;
            default:
                $hash = null;
        }

        return $hash::hash($data);
    }

    /// Key Management --->

    /**
     * @param string $data
     *
     * @return PrivateKey
     * @throws VirgilCryptoException
     */
    private function importInternalPrivateKey(string $data): PrivateKey
    {
        try {
            $keyProvider = new KeyProvider();

            $keyProvider->useRandom($this->rnd);
            $keyProvider->setupDefaults();

            return $keyProvider->importPrivateKey($data);

        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param string $data
     *
     * @return PublicKey
     * @throws VirgilCryptoException
     */
    private function importInternalPublicKey(string $data): PublicKey
    {
        try {
            $keyProvider = new KeyProvider();

            $keyProvider->useRandom($this->rnd);
            $keyProvider->setupDefaults();

            return $keyProvider->importPublicKey($data);

        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param string $data
     *
     * @return VirgilKeyPair
     * @throws VirgilCryptoException
     */
    public function importPrivateKey(string $data): VirgilKeyPair
    {
        try {
            $privateKey = $this->importInternalPrivateKey($data);

            if ($privateKey->algId() == AlgId::RSA()) {
                $keyType = KeyPairType::getRsaKeyType($privateKey->bitLen());
            } else {
                $algId = $privateKey->algId();
                $keyType = KeyPairType::$algId();
            }

            $publicKey = $privateKey->extractPublicKey();

            $keyId = $this->computePublicKeyIdentifier($publicKey);

            $virgilPrivateKey = new VirgilPrivateKey($keyId, $privateKey, $keyType);
            $virgilPublicKey = new VirgilPublicKey($keyId, $publicKey, $keyType);

            return new VirgilKeyPair($virgilPrivateKey, $virgilPublicKey);

        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param PrivateKey $privateKey
     *
     * @return string
     * @throws VirgilCryptoException
     */
    private function exportInternalPrivateKey(PrivateKey $privateKey): string
    {
        try {
            $keyProvider = new KeyProvider();

            $keyProvider->useRandom($this->rnd);
            $keyProvider->setupDefaults();

            return $keyProvider->exportPrivateKey($privateKey);

        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Extracts public key from private key
     *
     * @param VirgilPrivateKey $virgilPrivateKey
     *
     * @return VirgilPublicKey
     * @throws VirgilCryptoException
     */
    public function extractPublicKey(VirgilPrivateKey $virgilPrivateKey)
    {
        try {
            $publicKey = $virgilPrivateKey->getPrivateKey()->extractPublicKey();

            return new VirgilPublicKey($virgilPrivateKey->getIdentifier(), $publicKey, $virgilPrivateKey->getKeyType());
        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param PublicKey $publicKey
     *
     * @return string
     * @throws VirgilCryptoException
     */
    private function exportInternalPublicKey(PublicKey $publicKey): string
    {
        try {
            $keyProvider = new KeyProvider();

            $keyProvider->useRandom($this->rnd);
            $keyProvider->setupDefaults();

            return $keyProvider->exportPublicKey($publicKey);
        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Imports public key from DER or PEM format
     *
     * @param string $data
     *
     * @return VirgilPublicKey
     * @throws VirgilCryptoException
     */
    public function importPublicKey(string $data): VirgilPublicKey
    {
        try {
            $keyProvider = new KeyProvider();

            $keyProvider->useRandom($this->rnd);
            $keyProvider->setupDefaults();

            $publicKey = $keyProvider->importPublicKey($data);

            if ($publicKey->algId() == AlgId::RSA()) {
                $keyType = KeyPairType::getRsaKeyType($publicKey->bitLen());
            } else {
                $algId = $publicKey->algId();
                $keyType = KeyPairType::$algId();
            }

            $keyId = $this->computePublicKeyIdentifier($publicKey);

            return new VirgilPublicKey($keyId, $publicKey, $keyType);

        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Exports public key
     *
     * @param VirgilPublicKey $publicKey
     *
     * @return string
     * @throws VirgilCryptoException
     */
    public function exportPublicKey(VirgilPublicKey $publicKey)
    {
        try {
            return $this->exportInternalPublicKey($publicKey->getPublicKey());
        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Export private key
     *
     * @param VirgilPrivateKey $privateKey
     *
     * @return string
     * @throws VirgilCryptoException
     */
    public function exportPrivateKey(VirgilPrivateKey $privateKey)
    {
        try {
            return $this->exportInternalPrivateKey($privateKey->getPrivateKey());
        } catch (Exception $e) {
            throw new VirgilCryptoException($e->getMessage(), $e->getCode());
        }
    }

    /// <--- Key Management
}