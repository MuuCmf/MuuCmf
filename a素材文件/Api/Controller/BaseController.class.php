<?php
/**
需要用户权限的公共类
 */
namespace Api\Controller;

use Think\Controller;

class BaseController extends Controller
{
	public $appid = 'dameng';    
    public $appsecret = 'hahailoveyou';
    public function _initialize()
    {   //验证TOKEN
		$token = I('get.token', '', 'op_t');
		
		$token = D('Api/Member')->jiemi($token); //解密客户端传过来的TOKEN
		$tokenClass = explode('.',$token);	
		$tokenInfo = D('user_token')->where(array('token' => $tokenClass[1]))->find();	
		if ($tokenInfo['token']!=$tokenClass[1]) {
			$data['status'] = 0;
			$data['info'] = '需要登录';
			$this->ajaxReturn($data,'json');
		}else{
			/* 记录登录SESSION */
			$user = D('member')->where(array('uid' => $tokenInfo['uid']))->find();
			$map['uid'] = $user['uid'];
			$map['role_id'] = $user['last_login_role'];
			$audit = D('UserRole')->where($map)->getField('status');
			$auth = array(
				'uid' => $user['uid'],
				'username' => get_username($user['uid']),
				'last_login_time' => $user['last_login_time'],
				'role_id' => $user['last_login_role'],
				'audit' => $audit,
            );
			session('user_auth', $auth);
            session('user_auth_sign', data_auth_sign($auth));		
	    }
    }
	
   /*  protected function defaultTabHash($tabHash)
    {
        $tabHash = op_t($_REQUEST['tabHash']) ?  op_t($_REQUEST['tabHash']): $tabHash;
        $this->assign('tabHash', $tabHash);
    } */

}