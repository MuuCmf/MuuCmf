<?php
// +----------------------------------------------------------------------
// | UCToo [ Universal Convergence Technology ]
// +----------------------------------------------------------------------
// | Copyright (c) 2014-2015 http://uctoo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Patrick <contact@uctoo.com>
// +----------------------------------------------------------------------
namespace Wshop\Model;
use Think\Model;

class WshopUserAddressModel extends Model{
	//用户收货地址
	protected $tableName='wshop_user_address';
	protected $_validate = array(
		array('name','1,64','收货人姓名长度不对',1,'length'),
		array('phone','0,16','电话长度不对',2,'length'),
		array('province','0,16','省长度不对',2,'length'),
		array('city','0,16','市长度不对',2,'length'),
		array('town','0,16','县长度不对',2,'length'),
		array('address','0,64','详细地址长度不对',2,'length'),

	);
	protected $_auto = array(
		array('modify_time', NOW_TIME, 3),
		array('status', '1', self::MODEL_INSERT),
//		array('parent_id', '0', self::MODEL_INSERT),
	);

	public function add_or_edit_user_address($user_address)
	{
		if(!empty($user_address['id']))
		{
			$ret = $this->where('id = '.$user_address['id'])->save($user_address);
		}
		else
		{
			$ret = $this->add($user_address);
		}
		return $ret;
	}

	public function delete_user_address($ids)
	{
		is_array($ids) || $ids = array($ids);
		return $this->where('id in ('.implode(',',$ids).')')->delete();
	}

	public function get_user_address_list($uid)
	{
		$map['user_id']=$uid;
		$ret = $this->where($map)->select();
		return $ret;
	}

	public function get_last_user_address_by_user_id($user_id)
	{
		return $this->where('user_id='.$user_id)->order('modify_time desc')->find();
	}

	public function get_user_address_by_id($id)
	{
		return $this->where('id = '.$id)->find();
	}
}

