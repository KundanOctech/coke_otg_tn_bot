<?php

namespace App\Helper;

use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\AES;
use phpseclib3\Exception\UnsupportedAlgorithmException;
use phpseclib3\Crypt\PublicKeyLoader;


class FlowHash
{
    private $privatePem = '-----BEGIN ENCRYPTED PRIVATE KEY-----
MIIFJDBWBgkqhkiG9w0BBQ0wSTAxBgkqhkiG9w0BBQwwJAQQ7VEPvbhApzOPTUgR
g+D2ggICCAAwDAYIKoZIhvcNAgkFADAUBggqhkiG9w0DBwQIDL/VKcosGFYEggTI
IsodZC3fq8rPQrw9ZGqEDoW+D2QX+8kO0zteEO8uU3Nd0V4rBhEDFI+PzmsepeXB
0zy51vD3UCYJMJoAnOKqeVPXiuH2hz25UKNNwyzPtZ/Hp24Y87vRO6ScUCTyPSSS
1xGpaSBITRSbo3RChntgCJTepGjma+52m/5KYIT1YvkgYC1SSuBb5JFInhDuSjte
ZzJpjN3wW0RCCAkyceBm92ZpEqe8z9jyGzNz1KDl41tiiGRuwPFPPgpCwN7W+pfH
KXNUNo6iB0T6FcLGx/9TpiI9cB//u+z9Ts5OUdHXJ3iR1DDWOVmd69q6d3pPIZRH
WEbjseNj1JdDO9Wd076j/KD3zivNZiPyLupsS7ufOfvFfpt1GyijAYOIA2AJfVRB
+jmEh+b2eh1Jtkf0zGWXbPL3D8fZFkiDJBoxmDq0SfkdtKVT8vkgDIXOgR4UMuIq
+yFFbVVHJJWiL5gaqEHGd711AfgAY6p1QyKUOBDRj2GWmt+mJPcxXr4F9Cw13Sb9
NHPwjQIWphcOmjOamu1e8px96e98ABARgr61Uo31MQZwKutyIZjiZ+dYyys10Fce
ttsHuPzW97+0plD6QKgb8DkDrtDih8mYuQp9BhXAQwglzz70bIxEgBhqwIojEszV
ENcMU0XPlHug33enRBUsNUWV8nq3glXRy1a2nACwtbNQRnkJZpj1H6B9/+0so9eP
m7HugEIs0loji2hZD9KqBCz5O2ZFem5i+5F53fuAPWLY9539EIBv12ciuoEjTGlG
9UlLz9o1cOzuWsdkgDche0JJvCfZ/xRv8cTbOUcilA1OeH9FZFhqzY75cjBg3Kq3
Dvd1k/zsVedUZAQ1vuSlhkUf25oj8orW/5aN89Y+uq08P22MQR8838ngpPRbTGTI
AkcYNde79H8WFdac0130FeyIE7vZIQhes0EsXA8gfWqdkIRSppdXJgL/y43TbHaA
rdikMaBzyVSHwXHT17wuwdNF1xHMLV96xtSN0lFhTsOq70OyKIpw1qh0RbeKMZBW
OsAqsu9Nyp2wRnzLh88TsbK016hO16J+SRTfDyoAU5SWAYiptUIBz8idJrurGLYk
2zWBBRMEGQ98E8OvIwv3WECpdMr56/e0GDe7UPWhtBabUmbeb8jwYYRMCFQwzcNi
hDS/ZWUmsvCYtkCtxRiPmCxouVUS9HFp0FrE2QVfh0LH29WnfBZEwCG9SKPukJep
bi0y3WXC9kMhtMHN11hOAuFiKoOnpsTPhg+LVj/59cz/xXtB3wjrS/1aluCYs6+Z
gl4s7H5redy007hgPrgnFB7reRF0FfrhvEg/7zmqWd51h/sQjJZE+Wd/GopUA0se
gDFLp9ZyjkBDRk8NTrMIK2LF7gTnhvJKp1MCtSPIGW53WAD1LwpjUd7F4b/2Lv9j
sDRFfdZEin7rMBraslPczuLOLz65XWLGkH/FeKeIWNOeCO/xFbX/XZi2xtieFs5Y
ThyeidbcQBhU51QrpGvFYBgxjuUW1Xb7Jwo4gg8/fsw1eTar9eSRd9adNz1hOc3b
EqK5etYVt0A97h+sxZWiGV/+Wx+5AA2TC5oRvM7U8f3EATKL5QOZDOE/wVn+lcy2
yhUGZlP/aOxdXWwKSQlWvsn6a4KzXHjx
-----END ENCRYPTED PRIVATE KEY-----';

    private $passphrase = 'Octech@10';

    public function decryptRequest($encryptedAesKeyEnc, $encryptedFlowDataEnc, $initialVector)
    {
        $encryptedAesKey = base64_decode($encryptedAesKeyEnc);
        $encryptedFlowData = base64_decode($encryptedFlowDataEnc);
        $initialVector = base64_decode($initialVector);

        try {
            $privateKey = PublicKeyLoader::load($this->privatePem, $this->passphrase)
                ->withPadding(RSA::ENCRYPTION_OAEP)
                ->withHash('sha256')
                ->withMGFHash('sha256');
        } catch (Exception) {
            return false;
        }


        try {
            $decryptedAesKey = $privateKey->decrypt($encryptedAesKey);
        } catch (Exception) {
            return false;
        }

        $TAG_LENGTH = 16;
        $ciphertextLength = strlen($encryptedFlowData) - $TAG_LENGTH;
        $encryptedBody = substr($encryptedFlowData, 0, $ciphertextLength);
        $authTag = substr($encryptedFlowData, -$TAG_LENGTH);

        $decryptedJson = openssl_decrypt(
            $encryptedBody,
            'aes-128-gcm',
            $decryptedAesKey,
            OPENSSL_RAW_DATA,
            $initialVector,
            $authTag
        );
        return [
            'decryptedBody' => json_decode($decryptedJson, true),
            'aesKeyBuffer' => $decryptedAesKey,
            'initialVectorBuffer' => $initialVector,
        ];
    }

    function encryptResponse($response, $aesKeyBuffer, $initialVectorBuffer)
    {
        // Flip IV bytes bitwise like in Node.js (~byte)
        $flipped_iv = '';
        for ($i = 0; $i < strlen($initialVectorBuffer); $i++) {
            $flipped_iv .= chr(~ord($initialVectorBuffer[$i]) & 0xFF);
        }

        // Create AES-128-GCM cipher
        $cipher = new AES('gcm');
        $cipher->setKey($aesKeyBuffer);
        $cipher->setNonce($flipped_iv);

        // Disable additional associated data (AAD) for parity with Node.js
        $cipher->setAAD('');

        // Encrypt JSON-encoded response
        $plaintext = json_encode($response);
        $ciphertext = $cipher->encrypt($plaintext);

        // Get authentication tag
        $authTag = $cipher->getTag();

        // Node.js concatenates: ciphertext + authTag
        $combined = $ciphertext . $authTag;

        // Base64 encode
        return base64_encode($combined);
    }

    function encryptResponse2(array $response, string $aesKeyBuffer, string $initialVectorBuffer): string
    {
        // Flip initial vector
        $flippedIv = $initialVectorBuffer;
        for ($i = 0; $i < strlen($flippedIv); $i++) {
            $flippedIv[$i] = ~$flippedIv[$i];
        }

        $authTag = '';
        $encryptedData = openssl_encrypt(
            json_encode($response),
            'aes-128-gcm',
            $aesKeyBuffer,
            OPENSSL_RAW_DATA,
            $flippedIv,
            $authTag
        );

        if ($encryptedData === false) {
            throw new Exception(
                500,
                "Failed to encrypt response data."
            );
        }

        // Combine encrypted data and auth tag, then encode as base64
        return base64_encode($encryptedData . $authTag);
    }
}
