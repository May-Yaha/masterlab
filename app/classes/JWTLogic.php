<?php
namespace main\app\classes;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Parser;

class JWTLogic
{

    const PARSER_STATUS_VALID = 'VALID';
    const PARSER_STATUS_INVALID = 'INVALID';
    const PARSER_STATUS_EXPIRED = 'EXPIRED';
    const PARSER_STATUS_EXCEPTION = 'EXCEPTION';

    private static $instance = null;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct()
    {

    }

    private function __clone()
    {

    }

    /**
     * @param int $uid
     * @param string $account
     * @return string
     */
    public function publish(int $uid, string $account)
    {
        $signer = new Sha256();
        $key = new Key(JWT_KEY);
        $builder = new Builder();
        $expired = JWT_TOKEN_EXPIRED;

        $time = time();
        $token = $builder
            // ->issuedBy(ROOT_URL)
            // ->identifiedBy($uid.'-'.$time, true)
            ->issuedAt($time)
            //->canOnlyBeUsedAfter($time + 60)
            ->expiresAt($time + $expired)
            ->withHeader('uid', $uid)
            ->withHeader('account', $account)
            ->getToken($signer, $key);
        return $token;
    }

    /**
     * @param int $uid
     * @param string $account
     * @return string
     */
    public function publishRefreshToken(int $uid, string $account)
    {
        $signer = new Sha256();
        $key = new Key(JWT_KEY);
        $builder = new Builder();

        $expired = JWT_REFRESH_TOKEN_EXPIRED;

        $time = time();
        $refreshToken = $builder
            // ->issuedBy(ROOT_URL)
            // ->identifiedBy($uid.'-'.$time, true)
            ->issuedAt($time)
            //->canOnlyBeUsedAfter($time + 60)
            ->expiresAt($time + $expired)
            ->withHeader('uid', $uid)
            ->withHeader('account', $account)
            ->getToken($signer, $key);
        return $refreshToken;
    }


    /**
     * @param string $token
     * @return array|null
     */
    public function parser(string $token)
    {
        $result = [];

        $signer = new Sha256();
        $key = new Key(JWT_KEY);

        try {
            $parse = (new Parser())->parse($token);
            //验证token合法性
            if (!$parse->verify($signer, $key)) {
                $result['msg'] = 'Invalid token';
                $result['code'] = self::PARSER_STATUS_INVALID;
                $result['available'] = false;
                return $result;
            }

            $result['headers'] = $parse->getHeaders();
            $result['claims'] = $parse->getClaims();
            $result['uid'] = $parse->getClaim('uid');
            $result['account'] = $parse->getClaim('account');

            //验证是否已经过期
            if ($parse->isExpired()) {
                $result['msg'] = 'Token Already expired';
                $result['code'] = self::PARSER_STATUS_EXPIRED;
                $result['available'] = false;
                return $result;
            }

            //获取数据
            //var_dump($parse->getClaims());
            //var_dump($parse->getHeaders());
            //echo $parse->getHeader('jti'); // will print "4f1g23a12aa"
            //echo $parse->getClaim('iss'); // will print "http://example.com"
            //echo $parse->getClaim('uid'); // will print "1"

            $result['msg'] = 'ok';
            $result['code'] = self::PARSER_STATUS_VALID;
            $result['available'] = true;

        } catch (\Exception $e) {
            //var_dump($e->getMessage());
            $result['msg'] = 'Invalid token.';
            $result['code'] = self::PARSER_STATUS_EXCEPTION;
            $result['available'] = false;
        }

        return $result;
    }

}