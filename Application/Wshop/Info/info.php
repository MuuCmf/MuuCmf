<?php
// +----------------------------------------------------------------------
// | UCToo [ Universal Convergence Technology ]
// +----------------------------------------------------------------------
// | Copyright (c) 2014-2015 http://uctoo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: UCT <contact@uctoo.com>
// +----------------------------------------------------------------------
return array(
    //模块名
    'name' => 'Wshop',
    //别名
    'alias' => '微信商城',
    //版本号
    'version' => '1.0.0',
    //是否商业模块,1是，0，否
    'is_com' => 1,
    //是否显示在导航栏内？  1是，0否
    'show_nav' => 0,
    //模块描述
    'summary' => '微信商城模块',
    //开发者
    'developer' => '北京火木科技有限公司',
    //开发者网站
    'website' => 'http://www.hoomuu.cn',
    //前台入口，可用U函数
    'entry' => 'Wshop/index/index',

    'admin_entry' => 'Admin/Wshop/index',

    'icon' => 'shopping-cart',

    'can_uninstall' => 1
);