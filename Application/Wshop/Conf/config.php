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
    //此处只做模板使用，具体替换在COMMON模块中的set_theme函数,该函数替换MODULE_NAME,DEFAULT_THEME两个值为设置值
    'TMPL_PARSE_STRING' => array(
        '__STATIC__' => __ROOT__ . '/Public/static',
        '__ZUI__' => __ROOT__ . '/Public/zui',
        '__ADDONS__' => __ROOT__ . '/Public/'. MODULE_NAME.'/Addons',
        '__COMMON__'=>__ROOT__ . '/Application/'.MODULE_NAME. '/Static/common',
        '__IMG__'    => __ROOT__ . '/Application/'.MODULE_NAME. '/Static/'.DEFAULT_THEME.'/images',
        '__CSS__'    => __ROOT__ . '/Application/'.MODULE_NAME. '/Static/'.DEFAULT_THEME.'/css',
        '__JS__'     => __ROOT__ . '/Application/'.MODULE_NAME. '/Static/'.DEFAULT_THEME.'/js',
        '__Theme__'     => __ROOT__ . '/Application/'.MODULE_NAME. '/Static/'.DEFAULT_THEME,
    ),

    'NEED_VERIFY'=>true,//此处控制默认是否需要审核，该配置项为了便于部署起见，暂时通过在此修改来设定。

);
