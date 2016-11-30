<?php


/* *
 * 验证 webhooks 签名方法：
 * raw_data：Ping++ 请求 body 的原始数据即 event ，不能格式化；
 * signature：Ping++ 请求 header 中的 x-pingplusplus-signature 对应的 value 值；
 * pub_key_path：读取你保存的 Ping++ 公钥的路径；
 * pub_key_contents：Ping++ 公钥，获取路径：登录 [Dashboard](https://dashboard.pingxx.com)->点击管理平台右上角公司名称->开发信息-> Ping++ 公钥
 */
function verify_signature($raw_data, $signature, $pub_key_path) {
    $pub_key_contents = file_get_contents($pub_key_path);
    // php 5.4.8 以上，第四个参数可用常量 OPENSSL_ALGO_SHA256
    return openssl_verify($raw_data, base64_decode($signature), $pub_key_contents, 'sha256');
}