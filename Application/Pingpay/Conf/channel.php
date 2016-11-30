<?php

return array(
        'alipay'=>array(
        	'name'=>'alipay',
        	'title'=>'支付宝app支付',
                'stitle'=>'支付宝',
        	'icon'=>'alipay.png',
        	'extra'=>array(
        		'extern_token'=>'',//开放平台返回的包含账户信息的 token（授权令牌，商户在一定时间内对支付宝某些服务的访问权限）。通过授权登录后获取的  alipay_open_id ，作为该参数的  value ，登录授权账户即会为支付账户，32 位字符串。
        		'rn_check'=>'F',//是否发起实名校验，T 代表发起实名校验；F 代表不发起实名校验。
        		//'buyer_account'=>''//支付完成将额外返回付款用户的支付宝账号。
        	)
        ),
        'alipay_wap'=>array(
        	'name'=>'alipay_wap',
        	'title'=>'支付宝手机网页支付',
                'stitle'=>'支付宝',
        	'icon'=>'alipay.png',
        	'extra'=>array(
        		'success_url'=>'http://muucmf.hoomuu.cn/pingpay/index/succee',//支付成功的回调地址。
        		'cancel_url'=>'',//支付取消的回调地址， app_pay 为true时，该字段无效。
        		'app_pay'=>'true',//是否使用支付宝客户端支付，该参数为true时，调用客户端支付。
        		'buyer_account'=>'',//支付完成将额外返回付款用户的支付宝账号。
        	)
        ),
        'alipay_pc_direct'=>array(
        	'name'=>'alipay_pc_direct',
        	'title'=>'支付宝 PC 网页支付',
                'stitle'=>'支付宝',
        	'icon'=>'alipay.png',
        	'extra'=>array(
        		'success_url'=>'http://muucmf.hoomuu.cn/pingpay/index/succee',////支付成功的回调地址。
        		'enable_anti_phishing_key'=>'',//是否开启防钓鱼网站的验证参数（如果已申请开通防钓鱼时间戳验证，则此字段必填）
        		'exter_invoke_ip'=>'',//客户端 IP ，用户在创建交易时，该用户当前所使用机器的IP（如果商户申请后台开通防钓鱼IP地址检查选项，此字段必填，校验用）。
        	)
        ),
        'wx'=>array(
        	'name'=>'wx',
        	'title'=>'微信 APP 支付',
                'stitle'=>'微信支付',
        	'icon'=>'weixin.png',
        	'extra'=>array(
        		'limit_pay'=>'',//指定支付方式，指定不能使用信用卡支付可设置为  no_credit 
        		'goods_tag'=>'',//商品标记，代金券或立减优惠功能的参数。
        		'open_id'=>'',//用户在商户  appid 下的唯一标识
        		//'bank_type'=>'',//支付完成后额外返回付款用户的付款银行类型  bank_type
        	)
        ),
        'wx_pub'=>array(
                'name'=>'wx_pub',
                'title'=>'微信公众号支付',
                'stitle'=>'微信支付',
                'icon'=>'weixin.png',
                'extra'=>array(
                        'limit_pay'=>'',//指定支付方式，指定不能使用信用卡支付可设置为  no_credit 
                        'goods_tag'=>'',//商品标记，代金券或立减优惠功能的参数。
                        'open_id'=>'',//用户在商户  appid 下的唯一标识
                        //'bank_type'=>'',//支付完成后额外返回付款用户的付款银行类型  bank_type
                )
        ),
        'wx_pub_qr'=>array(
        	'name'=>'wx_pub_qr',
        	'title'=>'微信公众号扫码支付',
                'stitle'=>'微信支付',
        	'icon'=>'weixin.png',
        	'extra'=>array(
        		'limit_pay'=>'no_credit',//指定支付方式，指定不能使用信用卡支付可设置为  no_credit 。
        		'product_id'=>'',//商品 ID，1-32 位字符串。此 id 为二维码中包含的商品 ID，商户自行维护。
        		'goods_tag'=>'',//商品标记，代金券或立减优惠功能的参数。
        		//'open_id'=>'',//支付完成后额外返回付款用户的微信  open_id 。
        		//'bank_type'=>'',//支付完成后额外返回付款用户的付款银行类型  bank_type 。
        	)
        ),
        'wx_wap'=>array(
        	'name'=>'wx_wap',
        	'title'=>'微信 WAP 支付',
                'stitle'=>'微信支付',
        	'icon'=>'weixin.png',
        	'extra'=>array(
        		'result_url'=>'http://muucmf.hoomuu.cn/pingpay/index/succee',//支付完成的回调地址。
        		'goods_tag'=>'',//商品标记，代金券或立减优惠功能的参数。
        		//'open_id'=>'',//支付完成后额外返回付款用户的微信  open_id 。
        		//'bank_type'=>'',//支付完成后额外返回付款用户的付款银行类型  bank_type 。
        	)
        ),
        'upacp'=>array(
        	'name'=>'upacp',
        	'title'=>'银联支付，即银联 APP 支付（2015 年 1 月 1 日后的银联新商户使用。若有疑问，请与 Ping++ 或者相关的收单行联系）',
                'stitle'=>'银联支付',
        	'icon'=>'UnionPay.png',
        	'extra'=>array(),//upacp无需extra参数
        ),
        'upacp_wap'=>array(
        	'name'=>'upacp_wap',
        	'title'=>'银联手机网页支付（2015 年 1 月 1 日后的银联新商户使用。若有疑问，请与 Ping++ 或者相关的收单行联系）',
                'stitle'=>'银联支付',
        	'icon'=>'UnionPay.png',
        	'extra'=>array(
        		'result_url'=>'http://muucmf.hoomuu.cn/pingpay/index/succee',//支付完成的回调地址。
        	)
        ),
        'upacp_pc'=>array(
        	'name'=>'upacp_pc',
        	'title'=>'银联 PC 网页支付',
                'stitle'=>'银联支付',
        	'icon'=>'UnionPay.png',
        	'extra'=>array(
        		'result_url'=>'http://muucmf.hoomuu.cn/pingpay/index/succee',//支付完成的回调地址。
        	)
        ),







	);