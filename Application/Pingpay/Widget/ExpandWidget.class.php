<?php

namespace Pingpay\Widget;
use Think\Controller;

/**
 * 自定义页面内容widget
 */

class ExpandWidget extends Controller{
	
	/* 显示指定分类的同级分类或子分类列表 */
	public function pingpay(){

		echo 'this is expand onlinepay page';

		//$this->display();
	}

	public function payment(){
		echo 'this is payment page';
	}
	
}