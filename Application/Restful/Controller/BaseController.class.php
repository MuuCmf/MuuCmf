<?php
/**
需要用户权限的公共类
 */
namespace Restful\Controller;

use Think\Controller\RestController;

class BaseController extends RestController
{
    protected $userModel;
    protected $signModel;
    protected $codeModel;

    public function _initialize()
    {   
    	/**
         *写在前面 一些思路整理 
         *API的有效访问URL包括以下三个部分： 
            1. 资源访问路径，如/v1/deal/find_deals; 
            2. 请求参数：即API对应所需的参数名和参数值param=value，多个请求参数间用&连接如deal_id=1-85462&appid=00000； 
            3. 签名串，由签名算法生成
         *
         *这是restful api的全局控制器，
         *因为各模块的业务逻辑不同，所有各模块的restful接口依然放置在各模块的控制器文件夹中
         *如需要用户权限，所有的模块的restful接口控制器都要继承这个控制器 ，即所有模块如果要提供restful接口服务都依赖restfulApi模块，而restfulApi模块只提供muucmf的系统功能
         *命名空间 use RestfulApi\Controller\BaseController;
         *
         * 
    	 *
    	 * 普通access_token
         * 客户端通过$timestamp、nonce、access_token(access_token是双方约定的私钥)进行字典顺序排序后进行sha加密后再进行md5加密后转为大写，生成signature传递给服务端
    	 *
    	 * 用户授权token
    	 * 用户授权token与普通请求access_token 不同机制
    	 *
    	 * 通过用户名密码获取用户token,
    	 * 客户端接收到的用户token是经过加密处理的（加密避免算法泄露）
    	 * 返回给服务端也通过算法解密token,同时校验时间戳、user-agent或driveriD等
    	 */
        
        $this->userModel= D('Restful/User');
        $this->signModel= D('Restful/Sign');
        $this->codeModel= D('Restful/Code');  //返回码及信息

        /*测试阶段可注释掉*/
        //$this->signature(); //验证请求合法性
    }
    /**
     * 通用接口验证方法
     * @return [type] [description]
     */
    public function signature(){
        //获取客户端传过来的参数
        $timestamp = I('timestamp'); //时间戳
        $nonce = I('nonce');
        $signature = I('signature');
        $res = $this->signModel->checkSign($timestamp,$noce,$signature);
        $sTime = time(); //获取服务器时间戳
        if(($sTime-60)>$timestamp || ($sTime+60)<$timestamp || (!$res)){
            $result = $this->codeModel->code(400);
            $this->response($result,'json');
        }
    }
    
    /**
     * 通用需要登录验证
     * @return [type] [description]
     */
    public function _needLogin(){
        //验证用户授权TOKEN
        $token = I('get.token', '', 'text');
        if($token){
            $uid = $this->userModel->_checkToken($token);//验证用户Token合法性
            if (!$uid) {
                $data['info'] = '需要登录';
                $data['code'] = C('NEED_LOGIN');
                $this->response($data,'json');
            }
        }else{
            $data['info'] = '需要用户授权token';
            $data['code'] = C('NEED_LOGIN');
            $this->response($data,'json');
        }
    }

}