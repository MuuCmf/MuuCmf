<?php

namespace Wshop\Controller;

use Think\Controller;

class ApiController extends Controller {

	/**
	 * 校验微信接口配置信息
	 */
	public function api()
	{
		$echoStr = $_GET["echostr"];
		if($this->checkSignature()){
			echo $echoStr;
			exit;
		}
	}
	private function checkSignature()
	{
		// you must define TOKEN by yourself

		$token = $this->weinfo['token'];
		if (!$token) {
			throw new Exception('TOKEN is not defined!');
		}
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];

		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );

		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}