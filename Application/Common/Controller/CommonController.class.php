<?php

namespace Common\Controller;

use Think\Controller;

class CommonController extends Controller {

	protected $hello;

	public function _initialize()
    {

        $this->hello = 'hello world';

		$this->assign('hello',$hello);

    }
}