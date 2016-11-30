<?php


namespace Pingpay\Model;


use Think\Model;

class PingpayOrderModel extends Model{

    protected $_validate  =  array(   
        array('subject','require','充值类型不能为空！'), //默认情况下用正则进行验证
        array('body','require','描述不能为空！'),
        //array('body',array(1,31),'请选择应用分类！',1,'between',''), 
        //array('description','require','简短描述不能为空！'), 
        array('channel','require','支付方式不能为空！'),
        array('amount','require','充值金额不能为空！'),
        array('amount',array(1,9999999),'充值金额太离谱了!',1,'between',''),
    );

    public function editData($data)
    {
        if($data['id']){
            $res=$this->save($data);
        }else{
            $data['created'] = time();
            $res=$this->add($data);
        }
        return $res;
    }

    public function getDataById($id)
    {
        if($id){
            $map['id']=$id;
            $data=$this->where($map)->find();
            return $data;
        }
        return null;
    }

    public function getData($map)
    {
        if($map){
            $data=$this->where($map)->find();
            return $data;
        }
        return null;
    }

    public function getListByPage($map,$page=1,$order='created desc',$field='*',$r=20)
    {
        $totalCount=$this->where($map)->count();
        if($totalCount){
            $list=$this->where($map)->page($page,$r)->order($order)->field($field)->select();
        }
        return array($list,$totalCount);
    }

    public function getList($map,$order='view desc',$limit=5,$field='*')
    {
        $lists = $this->where($map)->order($order)->limit($limit)->field($field)->select();
        return $lists;
    }

    public function setDead($ids)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $map['id']=array('in',$ids);
        $res=$this->where($map)->setField('dead_line',time());
        return $res;
    }
    /**
    *设置为删除状态
    **/
    public function setDel($ids)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $map['id']=array('in',$ids);
        $res=$this->where($map)->setField('status',-1);
        return $res;
    }



} 