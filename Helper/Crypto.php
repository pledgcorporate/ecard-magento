<?php

namespace Pledg\PledgPaymentGateway\Helper;

use Firebase\JWT\JWT;

class Crypto
{
    /**
     * @param array  $payload
     * @param string $secretKey
     *
     * @return string
     */
    public function encode(array $payload, string $secretKey): string
    {
        return JWT::encode($payload, $secretKey);
    }
}
