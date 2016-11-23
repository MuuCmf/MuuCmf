<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-27
 */

namespace Muucmf\Model;


use Think\Model;

class SysupdateModel extends Model{

    public function editData($data)
    {
        if($data['id']){
            $data['update_time']=time();
            $res=$this->save($data);
        }else{
            $data['create_time']=$data['update_time']=time();
            $res=$this->add($data);
        }
        return $res;
    }

    public function getData($id)
    {
        if($id>0){
            $map['id'] = $id;
            $data = $this->where($map)->find();
            return $data;
        }else{
            return null;
        }
    }

    public function getListByPage($map,$page=1,$order='update_time desc',$field='*',$r=20)
    {
        $totalCount=$this->where($map)->count();
        if($totalCount){
            $list=$this->where($map)->page($page,$r)->order($order)->field($field)->select();
        }
        return array($list,$totalCount);
    }
    public function getVersionData($version)
    {
        if($version){
            $map['enable_version']=$version;
            $map['status']=1;
            $data=$this->where($map)->find();
            return $data;
        }
        return null;
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
    /**
    *真实删除内容
    **/
    public function setTrueDel($ids)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $map['id']=array('in',$ids);
        $res=$this->where($map)->delete();
        return $res;
    }

} 