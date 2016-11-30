<?php
/**

 */

return array(
    //模块名
    'name' => 'Pingpay',
    //别名
    'alias' => 'Ping++支付',
    //版本号
    'version' => '1.0.0',
    //是否商业模块,1是，0，否
    'is_com' => 0,
    //是否显示在导航栏内？  1是，0否
    'show_nav' => 1,
    //模块描述
    'summary' => 'Ping++支付管理模块，用户可以发起活动',
    //开发者
    'developer' => '北京火木科技有限公司',
    //开发者网站
    'website' => 'http://www.hoomuu.com',
    //前台入口，可用U函数
    'entry' => 'Pingpay/index/index',

    'admin_entry' => 'Admin/Pingpay/index',

    'icon' => 'archive',

    'can_uninstall' => 1
);