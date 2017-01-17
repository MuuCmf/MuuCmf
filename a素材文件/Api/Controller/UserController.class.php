<?php
/**
     * APP json接口
	 * 使用者用户中心
*/
namespace Api\Controller;

use Common\Model\FollowModel;
use Think\Controller\RestController;
use User\Api\UserApi;
require_once APP_PATH . 'User/Conf/config.php';

class UserController extends RestController
{
/*     public function _initialize(){   //初始化验证TOKEN
		$access_token = C('ACCESS_TOKEN');
		$tokeninfo = I('get.access_token', '', 'op_t');
			if ($access_token!=$tokeninfo) {
				$result['info'] = 'access_token有误，无法获取数据';
				$this->response($result,'json');
			}
	} */
	protected $allowMethod    = array('get','post','put'); // REST允许的请求类型列表
    protected $allowType      = array('html','xml','json'); // REST允许请求的资源类型列表
	
	//获取用户基本信息
    public function index()
    {
	 switch ($this->_method){
		case 'get': //get请求处理代码
		$aUid = I('get.uid',0,'intval');
		if($aUid){
			$map['uid'] = $aUid;
			$userData=M('member')->where($map)->find();
			if($userData){
				$user_info = query_user(array('avatar32','avatar128','mobile','email','sex','title','signature','score'), $aUid);
				$result['info'] = '请求成功';
				$result['data'] = $userData;
				$result['userinfo'] = $user_info;
			}else{
				$result['info'] = '参数错误，uid不存在或未定义';
			}
		}else{
			$result['info'] = '参数错误，没有有效的uid';
		}
		$result['code'] = 200;
		$this->response($result,'json');
		
		break;
		case 'post'://post请求处理代码
			//post用来修改用户基本信息
			$aOpen_id = I('post.open_id','',op_t);
			$uid = I('uid',0,'intval');
			$mobile = I('mobile',0,intval);
			$email = I('email','',op_t);
			$emailCode = I('emailCode',0,intval);
			$nickname = I('nickname','',op_t);
			$sex = I('sex');
			$signature = I('signature','',op_t);
			//验证open_id
			$access_openid=D('Member')->access_openid($aOpen_id);
			if($access_openid){
					$udata['id'] = $uid;
					if($mobile && $mobile!=0) {
						$udata['mobile'] = $mobile;
					}

					if($email){
						$map['account']=$email;
						$map['verify']=$emailCode;
						$ret=M('Verify')->where($map)->find();
						if($ret){
							$udata['email'] = $email;
						}else{
							$result['info'] = '邮箱和验证不匹配';
							$this->response($result,'json');
						}
					}
					
					$mdata['uid'] = $uid;
					if($nickname){
					$mdata['nickname'] = $nickname;
					}
					if($sex){
						if($sex==1 || $sex==2 || $sex==0){
						$mdata['sex'] = $sex;
						}
					}
					if($signature){
					$mdata['signature'] = $signature;
					}
					$User = M("Member"); // 实例化User对象
					if (!$User->create($mdata)){
						// 如果创建失败 表示验证没有通过 输出错误提示信息
						$result['info'] = $User->getError();
						$this->response($result,'json');
					}else{
						 // 验证通过 可以进行其他数据操作
						$User->save($mdata);
					}
					$Ucmember = UCenterMember();
					if (!$Ucmember->create($udata)){
						// 如果创建失败 表示验证没有通过 输出错误提示信息
						$result['info'] = $Ucmember->getErrorMessage($error_code = $Ucmember->getError());
						$this->response($result,'json');
					}else{
						 // 验证通过 可以进行其他数据操作
						$Ucmember->save($udata);
					}
					clean_query_user_cache($uid,array('nickname','mobile','email','sex','signature'));
					$result['info'] = '更新完成';
					$result['data'] = $mdata+$udata;
					$this->response($result,'json');
			}
		break;
	 }
    }
	
	/* 登录提交页面 */
    public function login()
    {
	switch ($this->_method){
		case 'get': //get请求处理代码
			$result['info'] = '无GET方法';
		break;
		case 'post'://post请求处理代码
			$result = A('Ucenter/Login', 'Widget')->doLogin();
			//var_dump($result);
			if ($result['status']) {
				$uid = $result['uid'];
				$map['uid'] = $uid; 
				$open_id = M('user_token')->field('token')->where($map)->find();
				//$token = D('Member')->jiami($uid.'|'.$open_id['token']);//加密token,每次使用token验证都需要解密操作
				//$jiemi = D('Member')->jiemi($token);
				$data = M('member')->where($map)->find(); 
				$user_info = query_user(array('avatar32','avatar64','avatar128','mobile','email','title'), $uid);
				
				$result['info'] = '登录成功';
				$result['open_id']=$open_id['token']; //用户持久登录token
				$result['data'] = $data;
				$result['user_info'] = $user_info;
				
			}
		break;
	}
	$result['code'] = 200;
	$this->response($result,'json');
    }
	
	public function register()
    {

        //获取参数
        $aUsername = $username = I('post.username', '', 'op_t');
        $aNickname = I('post.nickname', '', 'op_t');
        $aPassword = I('post.password', '', 'op_t');
        $aVerify = I('post.verify', '', 'op_t');
        $aRegVerify = I('post.reg_verify', '', 'op_t');
        $aRegType = I('post.reg_type', '', 'op_t');
        $aStep = I('get.step', 'start', 'op_t');
        $aRole = I('post.role', 0, 'intval');



        if (!modC('REG_SWITCH', '', 'USERCONFIG')) {
            //$this->error(L('_ERROR_REGISTER_CLOSED_'));//大L方法，语言定义
			$result['info'] = L('_ERROR_REGISTER_CLOSED_');
			$this->response($result,'json');
        }

		$result['info'] = 'ERROR';
        if (IS_POST) {
            //注册用户
            $return = check_action_limit('reg', 'ucenter_member', 1, 1, true);
            if ($return && !$return['state']) {
                //$this->error($return['info'], $return['url']);
				$result['info'] = $return['info'];
				$this->response($result,'json');
            }
            /* 移动的取消检测验证码 注释掉
            if (check_verify_open('reg')) {
                if (!check_verify($aVerify)) {
                    $this->error(L('_ERROR_VERIFY_CODE_').L('_PERIOD_'));
                }
            }*/
            if (!$aRole) {
                //$this->error(L('_ERROR_ROLE_SELECT_').L('_PERIOD_'));
				$result['info'] = L('_ERROR_ROLE_SELECT_').L('_PERIOD_');
				$this->response($result,'json');
            }

            if (($aRegType == 'mobile' && modC('MOBILE_VERIFY_TYPE', 0, 'USERCONFIG') == 1) || (modC('EMAIL_VERIFY_TYPE', 0, 'USERCONFIG') == 2 && $aRegType == 'email')) {
                if (!D('Verify')->checkVerify($aUsername, $aRegType, $aRegVerify, 0)) {
                    $str = $aRegType == 'mobile' ? L('_PHONE_') : L('_EMAIL_');
                    //$this->error($str . L('_FAIL_VERIFY_'));
					$result['info'] = $str . L('_FAIL_VERIFY_');
					$this->response($result,'json');
					
                }
            }
            $aUnType = 0;
            //获取注册类型
            check_username($aUsername, $email, $mobile, $aUnType);
            if ($aRegType == 'email' && $aUnType != 2) {
                //$this->error(L('_ERROR_EMAIL_FORMAT_'));
				$result['info'] = L('_ERROR_EMAIL_FORMAT_');
				$this->response($result,'json');
            }
            if ($aRegType == 'mobile' && $aUnType != 3) {
                //$this->error(L('_ERROR_PHONE_FORMAT_'));
				$result['info'] = L('_ERROR_PHONE_FORMAT_');
				$this->response($result,'json');
            }
            if ($aRegType == 'username' && $aUnType != 1) {
                //$this->error(L('_ERROR_USERNAME_FORMAT_'));
				$result['info'] = L('_ERROR_USERNAME_FORMAT_');
				$this->response($result,'json');
            }
            if (!check_reg_type($aUnType)) {
                //$this->error(L('_ERROR_REGISTER_NOT_OPENED_').L('_PERIOD_'));
				$result['info'] = L('_ERROR_REGISTER_NOT_OPENED_').L('_PERIOD_');
				$this->response($result,'json');
            }

            /* 注册用户 */
            $ucenterMemberModel=UCenterMember();
            $uid =$ucenterMemberModel ->register($aUsername, $aNickname, $aPassword, $email, $mobile, $aUnType);
            if (0 < $uid) { //注册成功
                $this->initInviteUser($uid, $aCode, $aRole);
                $ucenterMemberModel->initRoleUser($aRole, $uid); //初始化角色用户
                $uid = $ucenterMemberModel->login($username, $aPassword, $aUnType); //通过账号密码取到uid
                //D('Member')->login($uid, false, $aRole); //登陆

                //$this->success('', U('Ucenter/member/step', array('step' => get_next_step('start'))));
				$result['info'] = '注册成功';
				$result['code'] = 200;
				$this->response($result,'json');	
            } else { //注册失败，显示错误信息
				$result['info'] = $this->showRegError($uid);
				$this->response($result,'json');	
            }
        }	
    }
	
	public function logout()
    {
        //调用退出登录的API
        D('Member')->logout();
        $html='';
        if(UC_SYNC && is_login() != 1){
            include_once './api/uc_client/client.php';
            $html = uc_user_synlogout();
        }
		$result['info']=L('_SUCCESS_LOGOUT_').L('_PERIOD_');
		$result['code']=200;
		$this->response($result,'json');
    }
	
	public function uploadAvatar(){//上传头像
	
		$files = I('post.file','',op_t);
		$aUid = I('post.uid',0,intval);
		$aOpen_id = I('post.open_id','',op_t);
		
		//验证open_id
		$access_openid=D('Member')->access_openid($aOpen_id);
		if($access_openid){
			mkdir ("./Uploads/Avatar/".$aUid);
			$base64_image = str_replace(' ', '+', $files);
			//post的数据里面，加号会被替换为空格，需要重新替换回来，如果不是post的数据，则注释掉这一行
			if (preg_match('/^(data:\s*image\/(\w+);base64,)/',$base64_image,$result)){
				//dump($result);
				//匹配成功
				if($result[2] == 'jpeg'){
					$image_qz = uniqid();
					$image_name = $image_qz.'.jpg';
					//纯粹是看jpeg不爽才替换的
				}else{
					$image_qz = uniqid();
					$image_name = $image_qz.'.'.$result[2];
				}
			}
			$image_file = "Uploads/Avatar/".$aUid."/".$image_name; //未缩微的图片含后缀jpg,png
			$image_file_ok = "Uploads/Avatar/".$aUid."/".$image_qz; //缩微后的不含后缀
			$returnPath = '/'.$aUid.'/'.$image_name; //存入数据库的PATH
			
			if(file_put_contents($image_file, base64_decode(str_replace($result[1], '', $base64_image)))){
				
				$image = new \Think\Image(); 
				$image->open($image_file);
				// 按照原图的比例生成一个最大为150*150的缩略图并保存为thumb.jpg
				$image->thumb(512, 512)->save($image_file_ok.'_512_512.'.$result[2]);
				$image->thumb(256, 256)->save($image_file_ok.'_256_256.'.$result[2]);
				$image->thumb(128, 128)->save($image_file_ok.'_128_128.'.$result[2]);
				$image->thumb(64, 64)->save($image_file_ok.'_64_64.'.$result[2]);
				$image->thumb(32, 32)->save($image_file_ok.'_32_32.'.$result[2]);
				
				$driver = modC('PICTURE_UPLOAD_DRIVER','local','config');
				$data = array('uid' => $aUid, 'status' => 1, 'is_temp' => 0, 'path' => $returnPath,'driver'=> $driver, 'create_time' => time());
				$res = M('avatar')->where(array('uid' => $aUid))->save($data);
				if (!$res) {
					M('avatar')->add($data);
				}
				clean_query_user_cache($aUid, array('avatar256', 'avatar128', 'avatar64', 'avatar32', 'avatar512'));
				$return['info'] = '头像上传成功';
				$return['code'] = 200;
			}else{
				$return['info'] = 'error';
			}
			$this->response($return,'json');
		}else{
			$return['info'] = 'open_id error';
			$this->response($return,'json');
		}
		

    }
	 public function checkAccount()
    {
        $aAccount = I('post.account', '', 'op_t');
        $aType = I('post.type', '', 'op_t');
        if (empty($aAccount)) {
			$return['info'] = L('_EMPTY_CANNOT_').L('_EXCLAMATION_');
			$this->response($return,'json');
            //$this->error(L('_EMPTY_CANNOT_').L('_EXCLAMATION_'));
        }
        check_username($aAccount, $email, $mobile, $aUnType);
        $mUcenter = UCenterMember();
        switch ($aType) {
            case 'mobile':
                empty($mobile) && $this->error(L('_ERROR_PHONE_FORMAT_'));
                $id = $mUcenter->where(array('mobile' => $mobile))->getField('id');
                if ($id) {
					$return['info'] = L('_ERROR_PHONE_EXIST_');
					$this->response($return,'json');
                    //$this->error(L('_ERROR_PHONE_EXIST_'));
                }
                break;
        }
		$return['info'] = L('_SUCCESS_VERIFY_');
		$this->response($return,'json');
        //$this->success(L('_SUCCESS_VERIFY_'));
    }
	public function gps()
	{
		$aOpen_id = I('post.open_id','',op_t);
		//验证open_id
		$access_openid=D('Member')->access_openid($aOpen_id);

		if($access_openid){
				$aUid = I('post.uid',0,intval);
				$alng = I('post.lng');
				$alat = I('post.lat');

				$data['uid'] = $aUid;
				$data['lng'] = $alng;
				$data['lat'] = $alat;
				
				M('member')->save($data); // 根据条件更新记录
				$result['code'] = 200;
		}
		$return['info'] = '用户定位更新完成';
		$this->response($return,'json');

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
                $error = L('').modC('USERNAME_MIN_LENGTH',2,'USERCONFIG').'-'.modC('USERNAME_MAX_LENGTH',32,'USERCONFIG').L('_ERROR_LENGTH_2_').L('_EXCLAMATION_');
                break;
            case -2:
                $error = L('_ERROR_USERNAME_FORBIDDEN_').L('_EXCLAMATION_');
                break;
            case -3:
                $error = L('_ERROR_USERNAME_USED_').L('_EXCLAMATION_');
                break;
            case -4:
                $error = L('_ERROR_LENGTH_PASSWORD_').L('_EXCLAMATION_');
                break;
            case -5:
                $error = L('_ERROR_EMAIL_FORMAT_2_').L('_EXCLAMATION_');
                break;
            case -6:
                $error = L('_ERROR_EMAIL_LENGTH_').L('_EXCLAMATION_');
                break;
            case -7:
                $error = L('_ERROR_EMAIL_FORBIDDEN_').L('_EXCLAMATION_');
                break;
            case -8:
                $error = L('_ERROR_EMAIL_USED_2_').L('_EXCLAMATION_');
                break;
            case -9:
                $error = L('_ERROR_PHONE_FORMAT_2_').L('_EXCLAMATION_');
                break;
            case -10:
                $error = L('_ERROR_FORBIDDEN_').L('_EXCLAMATION_');
                break;
            case -11:
                $error = L('_ERROR_PHONE_USED_').L('_EXCLAMATION_');
                break;
            case -20:
                $error = L('_ERROR_USERNAME_FORM_').L('_EXCLAMATION_');
                break;
            case -30:
                $error = L('_ERROR_NICKNAME_USED_').L('_EXCLAMATION_');
                break;
            case -31:
                $error = L('_ERROR_NICKNAME_FORBIDDEN_2_').L('_EXCLAMATION_');
                break;
            case -32:
                $error =L('_ERROR_NICKNAME_FORM_').L('_EXCLAMATION_');
                break;
            case -33:
                $error = L('_ERROR_LENGTH_NICKNAME_1_').modC('NICKNAME_MIN_LENGTH',2,'USERCONFIG').'-'.modC('NICKNAME_MAX_LENGTH',32,'USERCONFIG').L('_ERROR_LENGTH_2_').L('_EXCLAMATION_');;
                break;
            default:
                $error = L('_ERROR_UNKNOWN_');
        }
        return $error;
    }
	
}