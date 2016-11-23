<?php

namespace Muucmf\Widget;
use Think\Controller;

/**
 * 自定义页面内容widget
 */

class ExpandWidget extends Controller{
	
	/* 显示指定分类的同级分类或子分类列表 */
	public function lists(){

		$this->display('Widget/expand');
	}
	
}