<?php
namespace Api\Controller;

use Think\Controller\RestController;

class VerifyController extends RestController
{
    /**
     * sendVerify 发送验证码
     * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
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
            //$this->error($str . L('_ERROR_OPTIONS_CLOSED_').L('_EXCLAMATION_'));
			$result['info'] = $str . L('_ERROR_OPTIONS_CLOSED_').L('_EXCLAMATION_');
			$this->response($result,'json');
        }


        if (empty($aAccount)) {
            //$this->error(L('_ERROR_ACCOUNT_CANNOT_EMPTY_'));
			$result['info'] = L('_ERROR_ACCOUNT_CANNOT_EMPTY_');
			$this->response($result,'json');
			
        }
        check_username($cUsername, $cEmail, $cMobile);
        $time = time();
        if($aType == 'mobile'){
            $resend_time =  modC('SMS_RESEND','60','USERCONFIG');
            if($time <= session('verify_time')+$resend_time ){
               // $this->error(L('_ERROR_WAIT_1_').($resend_time-($time-session('verify_time'))).L('_ERROR_WAIT_2_'));
				$result['info'] = L('_ERROR_WAIT_1_').($resend_time-($time-session('verify_time'))).L('_ERROR_WAIT_2_');
				$this->response($result,'json');
            }
        }


        if ($aType == 'email' && empty($cEmail)) {
            //$this->error(L('_ERROR__EMAIL_'));
			$result['info'] = L('_ERROR__EMAIL_');
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
            //$this->error(L('_ERROR_USED_1_') . $str . L('_ERROR_USED_2_').L('_EXCLAMATION_'));
			$result['info'] = L('_ERROR_USED_1_') . $str . L('_ERROR_USED_2_').L('_EXCLAMATION_');
			$this->response($result,'json');
        }

        $verify = D('Verify')->addVerify($aAccount, $aType, $uid);
        if (!$verify) {
            //$this->error(L('_ERROR_FAIL_SEND_').L('_EXCLAMATION_'));
			$result['info'] = L('_ERROR_FAIL_SEND_').L('_EXCLAMATION_');
			$this->response($result,'json');
        }

        $res =  A('Ucenter/'.ucfirst($aAction))->doSendVerify($aAccount, $verify, $aType);
        if ($res === true) {
            if($aType == 'mobile'){
                session('verify_time',$time);
            }
			
			$result['info'] = L('_ERROR_SUCCESS_SEND_');
			$this->response($result,'json');
            //$this->success(L('_ERROR_SUCCESS_SEND_'));
        } else {
            //$this->error($res);
			$result['info'] = $res;
			$this->response($result,'json');
        }

    }


}