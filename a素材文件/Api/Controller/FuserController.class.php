<?php
/**
     * APP json接口
	 * 服务者用户中心
*/
namespace Api\Controller;

use Think\Controller;


class UserController extends BaseController
{
	public function _initialize()
    {
        parent::_initialize();
        $uid = isset($_GET['uid']) ? op_t($_GET['uid']) : is_login();
        //调用API获取基本信息
        $this->userInfo($uid);
    }
	
    public function index()
    {
        echo 'app用户首页';
    }
	
	//用户登录
	public function login()
    {
        echo 'app用户登录';
    }
}