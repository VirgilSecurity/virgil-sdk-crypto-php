<?php
/**
 * Copyright (C) 2015-2020 Virgil Security Inc.
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *     (1) Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *     (2) Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *     (3) Neither the name of the copyright holder nor the names of its
 *     contributors may be used to endorse or promote products derived from
 *     this software without specific prior written permission.
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
 * Lead Maintainer: Virgil Security Inc. <support@virgilsecurity.com>
 */

namespace Virgil\Crypto\Core\IO;

use Virgil\Crypto\Core\Enum\VirgilCryptoError;
use Virgil\Crypto\Exceptions\VirgilCryptoException;

/**
 * Class FileInputStream
 *
 * @package Virgil\Crypto\Core\IO
 */
class FileInputStream implements InputStream
{
    /**
     * @var string
     */
    private $input;

    /**
     * @var resource
     */
    private $resource;

    /**
     * @var int
     */
    private $offset;

    /**
     * FileInputStream constructor.
     *
     * @param string $input
     *
     * @throws VirgilCryptoException
     */
    public function __construct(string $input)
    {
        $resource = fopen($input, "rb");
        if (!$resource) {
            throw new VirgilCryptoException(VirgilCryptoError::INPUT_STREAM_ERROR());
        }

        $this->input = $input;
        $this->resource = $resource;
        $this->offset = 0;
    }

    /**
     * @param string $buffer
     * @param int $size
     *
     * @return int
     */
    public function read(string &$buffer, int $size): int
    {
        $buffer = file_get_contents($this->input, false, null, $this->offset, $size);

        $len = strlen($buffer);
        $this->offset += $len;

        return $len;
    }

    public function __destruct()
    {
        fclose($this->resource);
    }
}