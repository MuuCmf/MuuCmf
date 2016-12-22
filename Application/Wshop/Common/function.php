<?php


/**
 * addSyncLoginData  增加sync_login表中数据
 * @param $uid
 * @return mixed
 */
function addSyncLoginData($uid,$openid,$access_token)
{
	$data['uid'] = $uid;
	$data['type_uid'] = $openid;
	$data['oauth_token'] = $access_token;
	$data['oauth_token_secret'] = $openid;
	$data['type'] = 'weixin';
	$syncModel =  D('sync_login');
	if(!$syncModel->where($map)->count()){
		$syncModel->add($data);
	}
	return true;
}
/**
 * 初始化角色用户信息
 * @param $role_id
 * @param $uid
 * @return bool
 */
function initRoleUser($role_id = 1, $uid)
{
	$memberModel = D('Member');
	$role = D('Role')->where(array('id' => $role_id))->find();
	$user_role = array('uid' => $uid, 'role_id' => $role_id, 'step' => "finish");
	if ($role['audit']) { //该角色需要审核
		$user_role['status'] = 2; //未审核
	} else {
		$user_role['status'] = 1;
	}
	$result = D('UserRole')->add($user_role);
	if (!$role['audit']) { //该角色不需要审核
		$memberModel->initUserRoleInfo($role_id, $uid);
	}
	$memberModel->initDefaultShowRole($role_id, $uid);

	return $result;
}
/**
 * saveAvatar  保存头像到本地
 * @param $url
 * @param $oid
 * @param $uid
 * @param $type
 */
function saveAvatar($url, $uid)
	{
		$driver = modC('PICTURE_UPLOAD_DRIVER', 'local', 'config');

		if ($driver == 'local') {
			mkdir('./Uploads/Avatar/' . $uid, 0777, true);
			$img = file_get_contents($url);
			$filename = './Uploads/Avatar/' . $uid . '/crop.jpg';
			file_put_contents($filename, $img);
			$data['path'] = '/' . $uid . '/crop.jpg';
		} else {
			$name =get_addon_class($driver);
			$class = new $name();
			$path = '/Uploads/Avatar/' . $uid . '/crop.jpg';
			$res = $class->uploadRemote($url,'Uploads/Avatar/' . $uid . '/crop.jpg');
			if($res !== false){
				$data['path'] =$res;
			}
		}
		$data['uid'] = $uid;
		$data['create_time'] = time();
		$data['status'] = 1;
		$data['is_temp'] = 0;
		$data['driver'] = $driver;
		D('avatar')->add($data);
	}

//根据openid获取微信用户表中的用户信息
function get_user_byopenid($openid){
    if(empty($openid)){
        return false;
    }
    $model = D('sync_login');
    $map['type_uid'] = $openid;
    $map['type'] = 'weixin';
    $res = $model->where($map)->find();
    return $res;

}

//金额单位换算
function price_convert($type='yuan',$price){
	if($type=='yuan'){
		$price = sprintf("%.2f",$price/100);
	}
	if($type=='fen'){
		$price = sprintf("%.2f",$price*100);
	}
	return $price;
}

/**
 * 设置主题
 */
function set_theme($theme=''){
        //判断是否存在设置的模板主题
        if(empty($theme)){
           $theme_name=C('DEFAULT_THEME');
        }else{
           if(is_dir (MODULE_PATH.'View/'.$theme )){
              $theme_name=$theme;             
           }else{
              $theme_name=C('DEFAULT_THEME');
           }           
           
        }
        //替换COMMON模块中设置的模板值    
        if(C('Current_Theme')){
            C('TMPL_PARSE_STRING',str_replace (C('Current_Theme') ,  $theme_name ,  C('TMPL_PARSE_STRING') ));        
        }else{
            C('TMPL_PARSE_STRING',str_replace ( "MODULE_NAME" ,  MODULE_NAME ,  C('TMPL_PARSE_STRING') ));    
            C('TMPL_PARSE_STRING',str_replace ( "DEFAULT_THEME" ,  $theme_name ,  C('TMPL_PARSE_STRING') ));
        }
        C('Current_Theme',$theme_name);
        C('DEFAULT_THEME', $theme_name);
}

