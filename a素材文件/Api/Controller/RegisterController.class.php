<?php
/**
 * 用户注册
*/
namespace Api\Controller;

use Think\Controller;
use User\Api\UserApi;

require_once APP_PATH . 'User/Conf/config.php';

class RegisterController extends Controller
{
	//用户注册
    public function index()
    {
    //获取参数
        $aUsername = $username = I('post.username', '', 'op_t');
        $aNickname = I('post.nickname', '', 'op_t');
		//$aNickname = I('post.username', '', 'op_t'); //首次注册，将昵称默认为手机号
        $aPassword = I('post.password', '', 'op_t');
        $aVerify = I('post.verify', '', 'op_t');
        $aRegVerify = I('post.reg_verify', 0, 'intval');
        $aRegType = I('post.reg_type', '', 'op_t');
        $aStep = I('get.step', 'start', 'op_t');
        $aRole = I('post.role', 0, 'intval');

        if (!modC('REG_SWITCH', '', 'USERCONFIG')) {
            $this->error('注册已关闭');
        }

        if (IS_POST) { //注册用户
            $return = check_action_limit('reg', 'ucenter_member', 1, 1, true);
            if ($return && !$return['state']) {
                $this->error($return['info'], $return['url']);
            }
            /* 检测验证码 */
            if (check_verify_open('reg')) {
                if (!check_verify($aVerify)) {
                    $this->error('验证码输入错误。');
                }
            }
            if (!$aRole) {
                $this->error('请选择角色。');
            }

            if (($aRegType == 'mobile' && modC('MOBILE_VERIFY_TYPE', 0, 'USERCONFIG') == 1) || (modC('EMAIL_VERIFY_TYPE', 0, 'USERCONFIG') == 2 && $aRegType == 'email')) {
                if (!D('Verify')->checkVerify($aUsername, $aRegType, $aRegVerify, 0)) {
                    $str = $aRegType == 'mobile' ? '手机' : '邮箱';
                    $this->error($str . '验证失败');
                }
            }
            $aUnType = 0;
            //获取注册类型
            check_username($aUsername, $email, $mobile, $aUnType);
            if ($aRegType == 'email' && $aUnType != 2) {
                $this->error('邮箱格式不正确');
            }
            if ($aRegType == 'mobile' && $aUnType != 3) {
                $this->error('手机格式不正确');
            }
            if ($aRegType == 'username' && $aUnType != 1) {
                $this->error('用户名格式不正确');
            }
            if (!check_reg_type($aUnType)) {
                $this->error('该类型未开放注册。');
            }
            

            /* 注册用户 */
            $uid = UCenterMember()->register($aUsername, $aNickname, $aPassword, $email, $mobile, $aUnType);
            if (0 < $uid) { //注册成功
			
                $this->initRoleUser($aRole, $uid); //初始化角色用户
			    
                if (modC('EMAIL_VERIFY_TYPE', 0, 'USERCONFIG') == 1 && $aUnType == 2) {
                    set_user_status($uid, 3);
                    $verify = D('Verify')->addVerify($email, 'email', $uid);
                    $res = $this->sendActivateEmail($email, $verify, $uid); //发送激活邮件
                    // $this->success('注册成功，请登录邮箱进行激活');
                }
                $uid = UCenterMember()->login($username, $aPassword, $aUnType); //通过账号密码取到uid
                D('Member')->login($uid, true, $aRole); //登陆
				$data['info'] = '注册成功';
	            $this->ajaxReturn($data,'json');
            } else { //注册失败，显示错误信息
                //$this->error($this->showRegError($uid));
				$data['info'] = $this->showRegError($uid);
	            $this->ajaxReturn($data,'json');
            }
        } else { //显示注册表单
            if (is_login()) {
                $data['info'] = '亲，您已经登录了';
	            $this->ajaxReturn($data,'json');
            }else{
				$data['info'] = '亲，您还没有登录哦';
	            $this->ajaxReturn($data,'json');
			}
        }
    }
	
	    /**
     * 初始化角色用户信息
     * @param $role_id
     * @param $uid
     * @return bool
     * @author 郑钟良<zzl@ourstu.com>
     */
    private function initRoleUser($role_id, $uid)
    {
        $memberModel = D('Member');
        $role = D('Role')->where(array('id' => $role_id))->find();
        $user_role = array('uid' => $uid, 'role_id' => $role_id, 'step' => "start");
        if ($role['audit']) { //该角色需要审核
            $user_role['status'] = 2; //未审核
        } else {
            $user_role['status'] = 1;
        }
        $result = D('UserRole')->add($user_role);
        if (!$role['audit']) { //该角色不需要审核
            $memberModel->initUserRoleInfo($role_id, $uid); //给予用户组
        }
        $memberModel->initDefaultShowRole($role_id, $uid);

        return $result;
    }
	
	    /**
     * 获取用户注册错误信息
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    public function showRegError($code = 0)
    {
        switch ($code) {
            case -1:
                $error = '用户名长度必须在4-32个字符以内！';
                break;
            case -2:
                $error = '用户名被禁止注册！';
                break;
            case -3:
                $error = '用户名被占用！';
                break;
            case -4:
                $error = '密码长度必须在6-30个字符之间！';
                break;
            case -5:
                $error = '邮箱格式不正确！';
                break;
            case -6:
                $error = '邮箱长度必须在4-32个字符之间！';
                break;
            case -7:
                $error = '邮箱被禁止注册！';
                break;
            case -8:
                $error = '邮箱被占用！';
                break;
            case -9:
                $error = '手机格式不正确！';
                break;
            case -10:
                $error = '手机被禁止注册！';
                break;
            case -11:
                $error = '手机号被占用！';
                break;
            case -20:
                $error = '用户名只能由数字、字母和"_"组成！';
                break;
            case -30:
                $error = '昵称被占用！';
                break;
            case -31:
                $error = '昵称被禁止注册！';
                break;
            case -32:
                $error = '昵称只能由数字、字母、汉字和"_"组成！';
                break;
             case -33:
                $error = '昵称不能少于两个字！';
                break; 
            default:
                $error = '未知错误24';
        }
        return $error;
    }
	
	    /**
     * checkAccount  ajax验证用户帐号是否符合要求
     * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
     */
    public function checkAccount()
    {
        $aAccount = I('post.account', '', 'op_t');
        $aType = I('post.type', '', 'op_t');
        if (empty($aAccount)) {
            $this->error('不能为空！');
        }
        check_username($aAccount, $email, $mobile, $aUnType);
        $mUcenter = UCenterMember();
        switch ($aType) {
            case 'username':
                empty($aAccount) && $this->error('用户名格式不正确！');
                $length = mb_strlen($aAccount, 'utf-8'); // 当前数据长度
                if ($length < 4 || $length > 32) {
                    $this->error('用户名长度在4-32之间');
                }


                $id = $mUcenter->where(array('username' => $aAccount))->getField('id');
                if ($id) {
                    $this->error('该用户名已经存在！');
                }
                preg_match("/^[a-zA-Z0-9_]{4,32}$/", $aAccount, $result);
                if (!$result) {
                    $this->error('只允许字母和数字和下划线！');
                }
                break;
            case 'email':
                empty($email) && $this->error('邮箱格式不正确！');
                $length = mb_strlen($email, 'utf-8'); // 当前数据长度
                if ($length < 4 || $length > 32) {
                    $this->error('邮箱长度在4-32之间');
                }

                $id = $mUcenter->where(array('email' => $email))->getField('id');
                if ($id) {
                    $this->error('该邮箱已经存在！');
                }
                break;
            case 'mobile':
                empty($mobile) && $this->error('手机格式不正确！');
                $id = $mUcenter->where(array('mobile' => $mobile))->getField('id');
                if ($id) {
                    $this->error('该手机号已经存在！');
                }
                break;
        }
        $this->success('验证成功');
    }
	
}