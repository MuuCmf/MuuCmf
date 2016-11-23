<?php

namespace Addons\Wxpay;
use Common\Controller\Addon;
use Com\TPWechat;
use Com\Wxauth;
use Common\Model\UcuserModel;

/**
 * 微信支付插件
 * @author UCToo
 */

    class WxpayAddon extends Addon{

        public $info = array(
            'name'=>'Wxpay',
            'title'=>'微信支付',
            'description'=>'微信支付集成插件',
            'status'=>1,
            'author'=>'UCToo',
            'version'=>'3.0'
        );

        public function install(){

            return true;
        }

        public function uninstall(){

            return true;
        }


        /**
         * 实现的wxpay钩子方法，对微信支付进行初始化，在需要需要集成微信支付的页面通过 {:hook('wxpay')}; 调用
         * @params string $params   未启用
         * @return void      hook函数木有返回值
         * 注意：
         */
        public function wxpay($params){

            $config = $this->getConfig();
            $this->assign('addons_config', $config);
            $this->display('widget');
        }

    }