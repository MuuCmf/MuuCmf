<?php
namespace Restful\Controller;

use Think\Controller\RestController;

class VerifyController extends RestController
{
    /**
     * sendVerify 发送验证码
     */
    public function sendVerify()
    {
		$uid = I('uid',0,'intval');
        $aAccount = $cUsername = I('post.account', '', 'op_t');
        $aType = I('post.type', '', 'op_t');
        $aType = $aType == 'mobile' ? 'mobile' : 'email';
        $aAction = I('post.action', 'config', 'op_t');
        if (!check_reg_type($aType)) {
            $str = $aType == 'mobile' ? L('_PHONE_') : L('_EMAIL_');
            
			$result['info'] = $str . L('_ERROR_OPTIONS_CLOSED_').L('_EXCLAMATION_');
            $result['code'] = C('SUCCESS');
			$this->response($result,'json');
        }


        if (empty($aAccount)) {

			$result['info'] = L('_ERROR_ACCOUNT_CANNOT_EMPTY_');
            $result['code'] = C('SUCCESS');
			$this->response($result,'json');
			
        }
        check_username($cUsername, $cEmail, $cMobile);
        $time = time();
        if($aType == 'mobile'){
            $resend_time =  modC('SMS_RESEND','60','USERCONFIG');
            if($time <= session('verify_time')+$resend_time ){
               
				$result['info'] = L('_ERROR_WAIT_1_').($resend_time-($time-session('verify_time'))).L('_ERROR_WAIT_2_');
                $result['code'] = C('SUCCESS');
				$this->response($result,'json');
            }
        }


        if ($aType == 'email' && empty($cEmail)) {
            //$this->error(L('_ERROR__EMAIL_'));
			$result['info'] = L('_ERROR__EMAIL_');
            $result['code'] = C('SUCCESS');
			$this->response($result,'json');
        }
        if ($aType == 'mobile' && empty($cMobile)) {
            //$this->error(L('_ERROR_PHONE_'));
			$result['info'] = L('_ERROR_PHONE_');
			$this->response($result,'json');
        }

        $checkIsExist = UCenterMember()->where(array($aType => $aAccount))->find();
        if ($checkIsExist) {
            $str = $aType == 'mobile' ? L('_PHONE_') : L('_EMAIL_');
            
			$result['info'] = L('_ERROR_USED_1_') . $str . L('_ERROR_USED_2_').L('_EXCLAMATION_');
            $result['code'] = C('SUCCESS');
			$this->response($result,'json');
        }

        $verify = D('Verify')->addVerify($aAccount, $aType, $uid);
        if (!$verify) {
			$result['info'] = L('_ERROR_FAIL_SEND_').L('_EXCLAMATION_');
            $result['code'] = C('SUCCESS');
			$this->response($result,'json');
        }
        //ucfirst() 函数把字符串中的首字符转换为大写。
        $res =  A('Ucenter/'.ucfirst('Member'))->doSendVerify($aAccount, $verify, $aType);
        if ($res === true) {
            if($aType == 'mobile'){
                session('verify_time',$time);
            }
			
			$result['info'] = L('_ERROR_SUCCESS_SEND_');
            $result['code'] = C('SUCCESS');
			$this->response($result,'json');
        } else {
			$result['info'] = $res;
            $result['code'] = C('SUCCESS');
			$this->response($result,'json');
        }

    }
}