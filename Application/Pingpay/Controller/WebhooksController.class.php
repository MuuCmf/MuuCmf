<?php

namespace Pingpay\Controller;

use Think\Controller;

class WebhooksController extends BaseController
{

    protected $webhookModel;
    public function _initialize()
    {
        $this->webhookModel = D('Pingpay/Webhook');
        parent::_initialize();
    }
    
    
    public function index()
    {
        $raw_data = file_get_contents('php://input');

        $headers = \Pingpp\Util\Util::getRequestHeaders();
        // 签名在头部信息的 x-pingplusplus-signature 字段
        $signature = isset($headers['X-Pingplusplus-Signature']) ? $headers['X-Pingplusplus-Signature'] : NULL;

        $result = $this->verify_signature($raw_data, $signature);
        if ($result === 1) {
            // 验证通过
            //echo 'verification success';
        } elseif ($result === 0) {
            http_response_code(400);
            echo 'verification failed';
            exit;
        } else {
            http_response_code(400);
            echo 'verification error';
            exit;
        }

        $event = json_decode($raw_data, true);
        //支付成功后处理
        if ($event['type'] == 'charge.succeeded') {
            $data = $event['data']['object'];
            $moduleName = $data['metadata']['module'];
            if (file_exists(APP_PATH . $moduleName . '/Widget/WebhookWidget.class.php')){
                W($moduleName.'/Webhook/charge',array($data));
            }else{
                echo $moduleName.'模块webhook处理文件不存在';
                http_response_code(500);
            }
        }
        //退款成功后处理
        if ($event['type'] == 'refund.succeeded') {
            $refund = $event['data']['object'];
            // ...
            http_response_code(200); // PHP 5.4 or greater
        } 
    }

    private function verify_signature($raw_data, $signature) 
    {

        $pub_key_contents = $this->public_key;
        // php 5.4.8 以上，第四个参数可用常量 OPENSSL_ALGO_SHA256
        return openssl_verify($raw_data, base64_decode($signature), $pub_key_contents, 'sha256');
    }
    
    
}