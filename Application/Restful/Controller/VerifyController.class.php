<?php
namespace Restful\Controller;

use Think\Controller\RestController;

class VerifyController extends RestController
{
    protected $codeModel;
    public function _initialize()
    {   
        parent::_initialize();
        $this->codeModel= D('Restful/Code');  //返回码及信息
    }
    /**
     * sendVerify 发送验证码
     * account 手机号或邮箱
     * type mobile 或 email
     */
    public function sendVerify()
    {
		$uid = I('uid',0,'intval');
        $aAccount = $cUsername = I('post.account', '', 'text');
        $aType = I('post.type', '', 'text');
        $aType = $aType == 'mobile' ? 'mobile' : 'email';

        if (!check_reg_type($aType)) {
            $str = $aType == 'mobile' ? L('_PHONE_') : L('_EMAIL_');

            $result = $this->codeModel->code(3000);
			$result['info'] = $str . L('_ERROR_OPTIONS_CLOSED_').L('_EXCLAMATION_');
			$this->response($result,$this->type);
        }


        if (empty($aAccount)) {

            $result = $this->codeModel->code(3000);
			$result['info'] = L('_ERROR_ACCOUNT_CANNOT_EMPTY_');
			$this->response($result,$this->type);	
        }
        check_username($cUsername, $cEmail, $cMobile);
        $time = time();
        if($aType == 'mobile'){
            $resend_time =  modC('SMS_RESEND','60','USERCONFIG');
            if($time <= session('verify_time')+$resend_time ){

                $result = $this->codeModel->code(3001);
				$result['info'] = L('_ERROR_WAIT_1_').($resend_time-($time-session('verify_time'))).L('_ERROR_WAIT_2_');
				$this->response($result,$this->type);
            }
        }


        if ($aType == 'email' && empty($cEmail)) {
            
            $result = $this->codeModel->code(3000);
			$result['info'] = L('_ERROR__EMAIL_');
			$this->response($result,$this->type);
        }
        if ($aType == 'mobile' && empty($cMobile)) {

            $result = $this->codeModel->code(3000);
			$result['info'] = L('_ERROR_PHONE_');
			$this->response($result,$this->type);
        }

        $checkIsExist = UCenterMember()->where(array($aType => $aAccount))->find();
        if ($checkIsExist) {
            $str = $aType == 'mobile' ? L('_PHONE_') : L('_EMAIL_');

            $result = $this->codeModel->code(3000);
			$result['info'] = L('_ERROR_USED_1_') . $str . L('_ERROR_USED_2_').L('_EXCLAMATION_');
			$this->response($result,$this->type);
        }

        $verify = D('Verify')->addVerify($aAccount, $aType, $uid);
        if (!$verify) {
            $result = $this->codeModel->code(1005);
			$result['info'] = L('_ERROR_FAIL_SEND_').L('_EXCLAMATION_');
			$this->response($result,$this->type);
        }
        //ucfirst() 函数把字符串中的首字符转换为大写。
        $res =  A('Ucenter/'.ucfirst('Member'))->doSendVerify($aAccount, $verify, $aType);
        if ($res === true) {
            if($aType == 'mobile'){
                session('verify_time',$time);
            }

			$result = $this->codeModel->code(3002);
			$result['info'] = L('_ERROR_SUCCESS_SEND_');
			$this->response($result,$this->type);
        } else {
			
            $result = $this->codeModel->code(200);
            $result['info'] = $res;
			$this->response($result,$this->type);
        }

    }
}