<?php

namespace Wshop\Controller;

use Think\Controller;
use Com\TPWechat;
use Com\WechatAuth;
class AddressController extends BaseController {
		protected $user_address_model;

function _initialize()
	{
		parent::_initialize();
		$this->user_address_model = D('Wshop/WshopUserAddress');

	}

	public function address()
	{
		$this->init_user();
		$option['page']    = I('page', '', 'intval');
		$option['r']       = I('r', -1, 'intval');
		$option['user_id'] = $this->user_id;
		$type = I('type','','text');
		$user_address_list = $this->user_address_model->get_user_address_list($option);
		$this->assign('address', $user_address_list);
		$this->assign('type', $type);
		$this->display();

	}

	public function addaddress()
	{
		$this->init_user();
		if (IS_POST)
		{
			$select = I('select','','intval');
			if($select && ($id =I('id','','intval') ))
			{
				empty($id) || $user_address = $this->user_address_model->get_user_address_by_id($id);
				$user_address = $this->user_address_model->create($user_address);
			}
			else
			{
				$user_address = $this->user_address_model->create();
			}
			if (!$user_address)
			{

				$this->error($this->user_address_model->getError());
			}
			$user_address['user_id'] = $this->user_id;
			$ret                     = $this->user_address_model->add_or_edit_user_address($user_address);
			if ($ret)
			{
				$this->success('操作成功。', U('wshop/user_address'));
			}
			else
			{
				$this->error('操作失败。');
			}

		}
		else
		{
			$id = I('id','','intval');
			$address = $this->user_address_model->get_user_address_by_id($id);
			$this->assign('address', $address);
			$this->display();
		}
	}


}