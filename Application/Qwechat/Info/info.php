<?php
/**
 * 所属项目 110.
 * 开发者: 陈一枭
 * 创建日期: 2014-11-18
 * 创建时间: 10:14
 * 版权所有 想天软件工作室(www.ourstu.com)
 */

return array(
    //模块名
    'name' => 'Qwechat',
    //别名
    'alias' => '企业微信',
    //版本号
    'version' => '2.4.2',
    //是否商业模块,1是，0，否
    'is_com' => 0,
    //是否显示在导航栏内？  1是，0否
    'show_nav' => 0,
    //模块描述
    'summary' => '微信模块，轻便强大的微信模块',
    //开发者
    'developer' => '黄冈咸鱼计算机科技有限公司',
    //开发者网站
    'website' => 'http://www.ourstu.com',
    //前台入口，可用U函数
    'entry' => 'Wechat/index/index',

    'admin_entry' => 'Admin/Qwechat/member',

    'icon' => 'comments',

    'can_uninstall' => 1,

    'hide' => 1
);