<?php

/**
 * 前台配置文件
 * 所有除开系统级别的前台配置
 */

return array(

    // 预先加载的标签库
    'TAGLIB_PRE_LOAD' => 'OT\\TagLib\\Article,OT\\TagLib\\Think',

	'SHOW_ERROR_MSG' =>false,
	'ERROR_MESSAGE' =>'发生错误！',
	'TMPL_ACTION_ERROR' => 'Public:error',
	
	'ACCESS_TOKEN' => 'dameng'
);

