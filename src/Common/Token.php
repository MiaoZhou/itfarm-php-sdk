<?php

namespace ItFarm\Common;

class Token
{
    private $secret;

    public function __construct($secret)
    {
        if (!$secret || strlen($secret) != 32) {
            throw new \Exception('secret is not a 32 bytes string');
        }

        $this->secret = $secret;
    }

    public function validate($data)
    {
        if (!$data['token']) {
            throw new \Exception('param [token] not set in $data');
        }

        if ((int)$data['expire'] <= 0) {
            throw new \Exception('param [expire] not set in $data or not an unix timestamp');
        }

        if ($data['expire'] < time()) {
            throw new \Exception("token has expired " . (time() - intval($data['expire'])) . "'s agon");
        }

        $origin_token = $data['token'];
        unset($data['token']);

        if ($origin_token !== md5($this->buildStr($data) . "." . $this->secret)) {
            throw new \Exception('token not valid');
        }
    }

    public function genToken($data)
    {
        // 过期时间默认为60秒
        $data['expire'] = isset($data['expire']) ? $data['expire'] : (time() + 60);

        // token作为关键字，过滤掉
        unset($data['token']);

        $token = md5($this->buildStr($data) . "." . $this->secret);
        $data['token'] = $token;

        return $data;
    }

    /**
     * 将数组所有元素，按照'a=b&c=d'格式拼接
     *
     * @param array $data
     * @return string
     */
    private function buildStr($data)
    {
        ksort($data);

        $str = '';
        foreach ($data as $key => $val) {
            $str .= $key . '=' . $val . '&';
        }
        $str = substr($str, 0, -1);

        if (get_magic_quotes_gpc()) {
            return stripcslashes($str);
        }

        return $str;
    }
}
