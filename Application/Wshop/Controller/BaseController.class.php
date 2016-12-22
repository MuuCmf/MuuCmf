<?php

namespace Wshop\Controller;

use Think\Controller;
use Com\TPWechat;
use Com\WechatAuth;
class BaseController extends Controller {
	protected $weinfo;//公众号appid
	protected $appid;//公众号appid
	protected $uid;//会员id


	function _initialize()
	{

		$this->weinfo=array(
				'appid'=>modC('WSHOP_WX_APPID', '', 'Wshop'),
				'appsecret'=>modC('WSHOP_WX_APPSECRET', '', 'Wshop'),
				'token'=>modC('WSHOP_WX_TOKEN', '', 'Wshop'),
				'encodingaeskey'=>modC('WSHOP_WX_ENCODINGAESKEY', '', 'Wshop')
		);
		if(isWeixinBrowser()){//微信浏览器返回
			$this->appid = $this->weinfo['appid'];
			if($this->appid){
				$this->init_wechatJs($this->weinfo['appid'],$this->weinfo['appsecret'],$this->weinfo['token'],$this->weinfo['encodingaeskey']);
				$this->assign('mp_id', $this->appid);
			}
			$this->theme('mobile');
		}
		
		$this->uid = is_login();

		$shopConfig = array(
			'title'=>modC('WSHOP_SHOW_TITLE', '', 'Wshop'),
			'logo'=>modC('WSHOP_SHOW_LOGO', '', 'Wshop'),
			'desc'=>modC('WSHOP_SHOW_DESC', '', 'Wshop'),
		);
		$this->assign('shopConfig',$shopConfig);

		set_theme();
	}

	/**
	 * 初始化用户
	 */
	protected function init_user()
	{
		if (isWeixinBrowser()) {
			if(!$this->uid){
				$this->init_wxlogin();//微信登录
			}
		}
		if(!$this->uid){
			$this->error('需要登录');
		}
	}
	//初始化微信JS
	private function init_wechatJs($appid,$appsecret,$token,$encodingaeskey)
	{
		$isWeixinBrowser = isWeixinBrowser();
		if ($isWeixinBrowser){
			//初始化options信息
			$options['appid'] = $appid;
			$options['appsecret'] = $appsecret;
			$options['token'] = $token;
			$options['encodingaeskey'] = $encodingaeskey;

			$weObj = new TPWechat($options);
			$weObj->checkAuth();
			$js_ticket = $weObj->getJsTicket();
			if (!$js_ticket) {
				$this->error('获取js_ticket失败！');
			}
			$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$js_sign = $weObj->getJsSign($url);
			$this->assign('js_sign', $js_sign);
		}
	}

	/*
	 * 微信登陆网页授权
	 */
	private function init_wxlogin()
	{
		$isWeixinBrowser = isWeixinBrowser();
		if ($isWeixinBrowser){
			$options['appid'] = $this->weinfo['appid'];
			$options['appsecret'] = $this->weinfo['appsecret'];
			$options['redirect_uri'] = GetCurUrl();

			$this->weAuth = new WechatAuth($options);
			$code = I('code');
			if(!$code){
				$url = $this->weAuth->get_authorize_url('muucmf');
				header('location:'.$url);
			}else{
				$access_data = $this->weAuth->get_access_token($code);
				$access_token = $access_data['access_token']; //网页授权的access_token
				$openid = $access_data['openid']; //用户openid
				$res = get_user_byopenid($openid);
				if($res){
					//已绑定就返回用户uid
					$this->uid = $res['uid'];
					$this->loginWithoutpwd($this->uid);
				}else{
					//未绑定就获取微信用户信息注册一个新用户
					$wx_user_info = $this->weAuth->get_user_info($access_token,$openid);//获取微信用户信息
					//$user_info内包含sex,nick,head
					$user_info = $wx_user_info;
					$user_info['nick'] = $wx_user_info['nickname'];
					$user_info['head'] = $wx_user_info['headimgurl'];//头像
					$user_info['access_token'] = $access_token;
					//dump(array($user_info));exit;
					//将微信用户信息写入表中
					$this->uid = UCenterMember()->addSyncData('wx');
					D('Member')->addSyncData($this->uid, $user_info);
					initRoleUser(1, $this->uid); //初始化角色用户
					addSyncLoginData($this->uid,$user_info['openid'],$user_info['access_token']);// 记录数据到sync_login表中
					saveAvatar($user_info['head'], $this->uid);
					//登陆指定UID的用户
					$this->loginWithoutpwd($this->uid);
				}
			}
		}
	}
	/**
	 * loginWithoutpwd  使用uid直接登陆，不使用帐号密码
	 * @param $uid
	 */
	private function loginWithoutpwd($uid)
	{
		if (0 < $uid) { //UC登陆成功
			/* 登陆用户 */
			$rs = D('Member')->login($uid); //登陆
			if ($rs) { //登陆用户
				$this->success('登陆成功！', session('login_http_referer'));
			} else {
				$this->error('登陆发生错误');
			}
		}
	}

}