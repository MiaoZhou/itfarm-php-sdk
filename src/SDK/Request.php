<?php

namespace ItFarm\SDK;

use GuzzleHttp\Client;
use ItFarm\Common\Token;

class Request
{
    private $api_url;
    private $token_type;
    private $token_key;
    private $token_secret;

    private $token;

    public function __construct()
    {
        if ($_SERVER['servicekey'] && $_SERVER['servicesecret']) {
            $this->token_type = Constant::TOKEN_TYPE_SERVICE;
            $this->token_key = $_SERVER['servicekey'];
            $this->token_secret = $_SERVER['servicesecret'];
        } elseif ($_SERVER['appkey'] && $_SERVER['appsecret']) {
            $this->token_type = Constant::TOKEN_TYPE_APP;
            $this->token_key = $_SERVER['appkey'];
            $this->token_secret = $_SERVER['appsecret'];
        } else {
            throw new \Exception('unkown token type, there must have appkey or servicekey in ENV');
        }

        if (!$_SERVER['api_url']) {
            throw new \Exception('api_url not set');
        }

        $this->api_url = $_SERVER['api_url'];
        $this->token = new Token($this->token_secret);
    }

    /**
     * @param string $api       api
     * @param array|null $query 可选， url中的参数
     * @return array
     */
    private function doGet($api, $query = null)
    {
        $params = ['api_uri' => $api];

        // 必须使用自身运行环境中的
        if ($query == null || empty($query)) {
            $query = [];
        }
        $query[$this->token_type] = $this->token_key;

        $ret = $this->token->genToken($query);

        $query['expire'] = $ret['expire'];
        unset($query['api_uri']);

        $options = [
            'headers' => [
                'ITFARM-TOKEN' => $ret['token']
            ],
            'query' => $query
        ];

        $options['query'] = $query;

        return $this->doCall('GET', $api, $options);
    }

    /**
     * @param string $api       api
     * @param array|null $query 可选，url中的参数
     * @param array|null $data  可选，post data
     * @param bool $is_json     可选，是否以json格式提交，true则会将$data做json_encode()
     * @return array
     */
    public function doPost($api, $query = null, $data = null, $is_json = false)
    {
        $params = ['api_uri' => $api];

        if ($query == null || empty($query)) {
            $query = [];
        }
        $query[$this->token_type] = $this->token_key;
        $params = array_merge($params, $query);

        if (is_array($data)) {
            if ($is_json) {
                $params = array_merge($params,
                    ['post_body' => \GuzzleHttp\json_encode($data)]);
            } else {
                $params = array_merge($params, $query);
            }
        }

        $ret = $this->token->genToken($params);

        $query['expire'] = $ret['expire'];
        unset($query['api_uri']);

        $options = [
            'headers' => [
                'ITFARM-TOKEN' => $ret['token']
            ],
            'query' => $query
        ];

        if ($is_json) {
            $options['json'] = $data;
        } else {
            $options['form_params'] = $data;
        }

        return $this->doCall('POST', $api, $options);
    }

    private function doCall($method, $uri, $options)
    {
        $client = new Client(['base_uri' => $this->api_url]);
        try {
            $res = $client->request($method, $uri, $options);

            return \GuzzleHttp\json_decode($res->getBody(), true);
        } catch (\Exception $e) {
            return [
                'ret' => -1,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }
    }
}