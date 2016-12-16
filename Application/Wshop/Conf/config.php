<?php
// +----------------------------------------------------------------------
// | UCToo [ Universal Convergence Technology ]
// +----------------------------------------------------------------------
// | Copyright (c) 2014-2015 http://uctoo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Patrick <contact@uctoo.com>
// +----------------------------------------------------------------------

/**
 * 前台配置文件
 * 所有除开系统级别的前台配置
 */

return array(

    // 预先加载的标签库
    'TAGLIB_PRE_LOAD' => 'OT\\TagLib\\Article,OT\\TagLib\\Think',

    /* 主题设置 */
    'DEFAULT_THEME' => 'default', // 默认模板主题名称


    /* 模板相关配置 */
    'TMPL_PARSE_STRING' => array(
        '__STATIC__' => __ROOT__ . '/Public/static',
        '__ADDONS__' => __ROOT__ . '/Public/' . MODULE_NAME . '/Addons',
        '__IMG__' => __ROOT__ . '/Application/'.MODULE_NAME   . '/Static/images',
        '__CSS__' => __ROOT__ . '/Application/'.MODULE_NAME .'/Static/css',
        '__JS__' => __ROOT__ . '/Application/'.MODULE_NAME. '/Static/js',
        '__JS__' => __ROOT__ . '/Application/'.MODULE_NAME. '/Static/js',
        '__WEUI__' => __ROOT__ . '/Application/'.MODULE_NAME. '/Static/weui',
        '__ZUI__' => __ROOT__ . '/Public/zui',
		'__AUI__'=>__ROOT__.'/Public/amazeui/assets',
    ),

    //'TMPL_ACTION_ERROR' => MODULE_PATH . 'View/mobile/Public/error.html', // 默认错误跳转对应的模板文件
    //'TMPL_ACTION_SUCCESS' => MODULE_PATH . 'View/mobile/Public/success.html', // 默认成功跳转对应的模板文件

    'NEED_VERIFY'=>true,//此处控制默认是否需要审核，该配置项为了便于部署起见，暂时通过在此修改来设定。

);

