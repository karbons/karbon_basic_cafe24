<?php
/**
 * JSON Web Token implementation
 */
class JWT
{
    const ASN1_INTEGER = 0x02;
    const ASN1_SEQUENCE = 0x10;
    const ASN1_BIT_STRING = 0x03;

    public static $leeway = 0;
    public static $timestamp = null;

    public static $supported_algs = array(
        'ES384' => array('openssl', 'SHA384'),
        'ES256' => array('openssl', 'SHA256'),
        'HS256' => array('hash_hmac', 'SHA256'),
        'HS384' => array('hash_hmac', 'SHA384'),
        'HS512' => array('hash_hmac', 'SHA512'),
        'RS256' => array('openssl', 'SHA256'),
        'RS384' => array('openssl', 'SHA384'),
        'RS512' => array('openssl', 'SHA512'),
    );

    public static function decode($jwt, $key, array $allowed_algs = array())
    {
        $timestamp = is_null(static::$timestamp) ? time() : static::$timestamp;

        if (empty($key)) {
            throw new Exception('Key may not be empty');
        }
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            throw new Exception('Wrong number of segments');
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        if (null === ($header = self::jsonDecode(self::urlsafeB64Decode($headb64)))) {
            throw new Exception('Invalid header encoding');
        }
        if (null === $payload = self::jsonDecode(self::urlsafeB64Decode($bodyb64))) {
            throw new Exception('Invalid claims encoding');
        }
        if (false === ($sig = self::urlsafeB64Decode($cryptob64))) {
            throw new Exception('Invalid signature encoding');
        }
        if (empty($header->alg)) {
            throw new Exception('Empty algorithm');
        }
        if (empty(self::$supported_algs[$header->alg])) {
            throw new Exception('Algorithm not supported');
        }

        if (!in_array($header->alg, $allowed_algs)) {
            throw new Exception('Algorithm not allowed');
        }
        if ($header->alg === 'ES256' || $header->alg === 'ES384') {
            $sig = self::signatureToDER($sig);
        }

        if (is_array($key)) {
            if (isset($header->kid)) {
                if (!isset($key[$header->kid])) {
                    throw new Exception('"kid" invalid, unable to lookup correct key');
                }
                $key = $key[$header->kid];
            } else {
                throw new Exception('"kid" empty, unable to lookup correct key');
            }
        }

        if (!self::verify("$headb64.$bodyb64", $sig, $key, $header->alg)) {
            throw new Exception('Signature verification failed');
        }

        if (isset($payload->nbf) && $payload->nbf > ($timestamp + self::$leeway)) {
            throw new Exception('Cannot handle token prior to nbf');
        }

        if (isset($payload->iat) && $payload->iat > ($timestamp + self::$leeway)) {
            throw new Exception('Cannot handle token prior to iat');
        }

        if (isset($payload->exp) && ($timestamp - self::$leeway) >= $payload->exp) {
            throw new Exception('Expired token');
        }

        return $payload;
    }

    public static function encode($payload, $key, $alg = 'HS256', $keyId = null, $head = null)
    {
        $header = array('typ' => 'JWT', 'alg' => $alg);
        if ($keyId !== null) {
            $header['kid'] = $keyId;
        }
        if (isset($head) && is_array($head)) {
            $header = array_merge($head, $header);
        }
        $segments = array();
        $segments[] = self::urlsafeB64Encode(self::jsonEncode($header));
        $segments[] = self::urlsafeB64Encode(self::jsonEncode($payload));
        $signing_input = implode('.', $segments);

        $signature = self::sign($signing_input, $key, $alg);
        $segments[] = self::urlsafeB64Encode($signature);

        return implode('.', $segments);
    }

    public static function sign($msg, $key, $alg = 'HS256')
    {
        if (empty(self::$supported_algs[$alg])) {
             throw new Exception('Algorithm not supported');
        }
        list($function, $algorithm) = self::$supported_algs[$alg];
        switch ($function) {
            case 'hash_hmac':
                return hash_hmac($algorithm, $msg, $key, true);
            case 'openssl':
                $signature = '';
                $success = openssl_sign($msg, $signature, $key, $algorithm);
                if (!$success) {
                     throw new Exception("OpenSSL unable to sign data");
                } else {
                    if ($alg === 'ES256') {
                        $signature = self::signatureFromDER($signature, 256);
                    }
                    if ($alg === 'ES384') {
                        $signature = self::signatureFromDER($signature, 384);
                    }
                    return $signature;
                }
        }
    }

    private static function verify($msg, $signature, $key, $alg)
    {
        if (empty(self::$supported_algs[$alg])) {
             throw new Exception('Algorithm not supported');
        }

        list($function, $algorithm) = self::$supported_algs[$alg];
        switch ($function) {
            case 'openssl':
                $success = @openssl_verify($msg, $signature, $key, $algorithm);
                if ($success === 1) {
                    return true;
                } elseif ($success === 0) {
                    return false;
                }
                throw new Exception('OpenSSL error');
            case 'hash_hmac':
            default:
                $hash = hash_hmac($algorithm, $msg, $key, true);
                if (function_exists('hash_equals')) {
                    return hash_equals($signature, $hash);
                }
                $len = min(self::safeStrlen($signature), self::safeStrlen($hash));
                $status = 0;
                for ($i = 0; $i < $len; $i++) {
                    $status |= (ord($signature[$i]) ^ ord($hash[$i]));
                }
                $status |= (self::safeStrlen($signature) ^ self::safeStrlen($hash));
                return ($status === 0);
        }
    }

    public static function jsonDecode($input)
    {
        return json_decode($input, false);
    }

    public static function jsonEncode($input)
    {
        return json_encode($input);
    }

    public static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    public static function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    private static function safeStrlen($str)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, '8bit');
        }
        return strlen($str);
    }

    private static function signatureToDER($sig)
    {
        list($r, $s) = str_split($sig, (int) (strlen($sig) / 2));
        $r = ltrim($r, "x00");
        $s = ltrim($s, "x00");
        if (ord($r[0]) > 0x7f) $r = "x00" . $r;
        if (ord($s[0]) > 0x7f) $s = "x00" . $s;

        return self::encodeDER(
            self::ASN1_SEQUENCE,
            self::encodeDER(self::ASN1_INTEGER, $r) .
            self::encodeDER(self::ASN1_INTEGER, $s)
        );
    }

    private static function encodeDER($type, $value)
    {
        $tag_header = 0;
        if ($type === self::ASN1_SEQUENCE) $tag_header |= 0x20;
        $der = chr($tag_header | $type);
        $der .= chr(strlen($value));
        return $der . $value;
    }

    private static function signatureFromDER($der, $keySize)
    {
        list($offset, $_) = self::readDER($der);
        list($offset, $r) = self::readDER($der, $offset);
        list($offset, $s) = self::readDER($der, $offset);
        $r = ltrim($r, "x00");
        $s = ltrim($s, "x00");
        $r = str_pad($r, $keySize / 8, "x00", STR_PAD_LEFT);
        $s = str_pad($s, $keySize / 8, "x00", STR_PAD_LEFT);
        return $r . $s;
    }

    private static function readDER($der, $offset = 0)
    {
        $pos = $offset;
        $size = strlen($der);
        $constructed = (ord($der[$pos]) >> 5) & 0x01;
        $type = ord($der[$pos++]) & 0x1f;
        $len = ord($der[$pos++]);
        if ($len & 0x80) {
            $n = $len & 0x1f;
            $len = 0;
            while ($n-- && $pos < $size) {
                $len = ($len << 8) | ord($der[$pos++]);
            }
        }
        if ($type == self::ASN1_BIT_STRING) {
            $pos++;
            $data = substr($der, $pos, $len - 1);
            $pos += $len - 1;
        } elseif (!$constructed) {
            $data = substr($der, $pos, $len);
            $pos += $len;
        } else {
            $data = null;
        }
        return array($pos, $data);
    }
}
