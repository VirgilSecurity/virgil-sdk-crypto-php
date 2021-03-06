<?php
/**
 * Copyright (C) 2015-2020 Virgil Security Inc.
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

namespace Virgil\Crypto\Core\VirgilKeys;

/**
 * Class VirgilKeyPair
 *
 * @package Virgil\Crypto\Core\VirgilKeys
 */
class VirgilKeyPair
{
    /**
     * @var VirgilPrivateKey
     */
    private $privateKey;

    /**
     * @var VirgilPublicKey
     */
    private $publicKey;

    /**
     * VirgilKeyPair constructor.
     *
     * @param VirgilPrivateKey $privateKey*
     * @param VirgilPublicKey  $publicKey
     */
    public function __construct(VirgilPrivateKey $privateKey, VirgilPublicKey $publicKey)
    {
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
    }

    /**
     * @return VirgilPrivateKey
     */
    public function getPrivateKey(): VirgilPrivateKey
    {
        return $this->privateKey;
    }

    /**
     * @return VirgilPublicKey
     */
    public function getPublicKey(): VirgilPublicKey
    {
        return $this->publicKey;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->publicKey->getIdentifier();
    }
}
