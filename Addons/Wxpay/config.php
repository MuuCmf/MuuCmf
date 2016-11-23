<?php
// +----------------------------------------------------------------------
// | UCToo [ Universal Convergence Technology ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.uctoo.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: patrick <contact@uctoo.com> <http://www.uctoo.com>
// +----------------------------------------------------------------------

return array(
	'APPID'=>array(//配置在表单中的键名 ,这个会是config[title]
		'title'=>'绑定支付的APPID:',//表单的文字
		'type'=>'text',		 //表单的类型：text、textarea、checkbox、radio、select等
		'value'=>'',			 //表单的默认值
	),
    'MCHID'=>array(
        'title'=>'商户号:',
        'type'=>'text',
        'value'=>'',
    ),
    'KEY'=>array(
        'title'=>'商户支付密钥（登录商户平台自行设置）:',
        'type'=>'text',
        'value'=>'',
    ),
    'APPSECRET'=>array(
        'title'=>'公众帐号secert（仅JSAPI支付的时候需要配置）:',
        'type'=>'text',
        'value'=>'',
    )
);
