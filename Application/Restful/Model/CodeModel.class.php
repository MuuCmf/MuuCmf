<?php
/**
 * @author 大蒙<59262424@qq.com>
 */

namespace Restful\Model;


use Think\Model;

class CodeModel extends Model{

	/**
	 * 返回码及说明
	 * @param  integer $code [description]
	 * @return [array]        [description]
	 */
	public function code($code=200,$info='请求成功'){

		switch ($code) {
			//接口授权部分
            case 400:
                $result['code'] = 400; //未授权的请求
                $result['info'] = '未授权的请求';
            break;
            case 200:
            	$result['code'] = 200;
            	$result['info'] = $info;
            break;
            //用户授权部分
            case 1000:
            	$result['code'] = 1000;
            	$result['info'] = '用户名错误';
            break;
            case 1001:
            	$result['code'] = 1001;
            	$result['info'] = '密码错误';
            break;
            case 1002:
            	$result['code'] = 1002;
            	$result['info'] = '需要登陆';
            break;
            case 1005;
                  $result['code'] = 1005;
                  $result['info'] = '验证失败';
            case 1003:
            	$result['code'] = 1003;
            	$result['info'] = '需要用户授权token';
            break;
            case 1004:
            	$result['code'] = 1004;
            	$result['info'] = '不存在的用户';
            break;

            //资源错误
            case 2000:
            	$result['code'] = 2000;
            	$result['info'] = '资源过期';
            break;
            case 2001:
            	$result['code'] = 2001;
            	$result['info'] = '资源不存在或已删除';
            break;
            //默认输出未知错误
            default:
            	$result['code'] = 10000;
            	$result['info'] = '未知错误';
		}

        return $result;
	}
}