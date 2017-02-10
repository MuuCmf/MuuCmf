<?php

namespace About\Model;

use Think\Model;

class AboutModel extends Model{

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

    public function getData($id){
        return $this->find($id);
    }

    public function getListByPage($map,$page=1,$order='sort asc,update_time desc',$field='*',$r=20)
    {
        $totalCount=$this->where($map)->count();
        if($totalCount){
            $list=$this->where($map)->page($page,$r)->order($order)->field($field)->select();
        }
        return array($list,$totalCount);
    }

    public function getList($map,$field='*',$order='sort asc')
    {
        $lists = $this->where($map)->field($field)->order($order)->select();
        return $lists;
    }

    public function setTrueDel($ids)
    {
        !is_array($ids)&&$ids=explode(',',$ids);
        $map['id']=array('in',$ids);
        $res=$this->where($map)->delete();
        return $res;
    }
} 