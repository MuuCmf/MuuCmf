<?php

namespace Expand\Model;


use Think\Model;

class ExpandRecordsModel extends Model{



    public function getListByPage($map,$page=1,$order='create_time desc',$field='*',$r=20)
    {
        $totalCount=$this->where($map)->count();
        if($totalCount){
            $list=$this->where($map)->page($page,$r)->order($order)->field($field)->select();
        }
        return array($list,$totalCount);
    }

	/*
	**获取用户购买应用数据
	*/
    public function getRecordData($uid,$expand_id)
    {
        $map['uid']=$uid;
        $map['expand_id']=$expand_id;
        $data=$this->where($map)->find();
        return $data;
    }
    /*
	**写入购买记录
    */
    public function addRecordData($data)
    {
	    $data['create_time']=time();
        $res=$this->add($data);
        //action_log('add_expandRecord', 'Expand', $res, is_login());
        return $res;
    }


    function query_expand($fields = null, $id = 0){
        //默认赋值
        if ($fields === null) {
            $fields = array('title', 'icon', 'description', 'id');
        }
        //如果fields不是数组，直接返回需要的值
        if (!is_array($fields)) {
            $result = query_expand(array($fields), $id);
            return $result[$fields];
        }

        //获取缓存过的字段
        //list($cacheResult, $field, $fields) =$this->getCachedFields($fields, $id);

        $map['id'] = $id;
        $result = M('expand')->where($map)->field($fields)->find();

        //写缓存
        //$result = $this->writeCache($id, $result);
        //合并结果，包括缓存
        //$result = array_merge($result, $cacheResult);

        //返回结果
        return $result;
    }

    public function getCachedFields($fields, $id)
    {
    //查询缓存，过滤掉已缓存的字段
        $cachedFields = array();
        $cacheResult = array();
        foreach ($fields as $field) {
            $cache = $this->read_query_expand_cache($id, $field);
            if ($cache !== false) {
                $cacheResult[$field] = $cache;
                $cachedFields[] = $field;
            }
        }
        //去除已经缓存的字段
        $fields = array_diff($fields, $cachedFields);
        return array($cacheResult, $field, $fields);
    }

    private function read_query_expand_cache($id, $field)
    {
        return S("query_expand_{$id}_{$field}");
    }
    private function write_query_expand_cache($id, $field, $value)
    {
        return S("query_expand_{$id}_{$field}", $value);
    }

    public function writeCache($id, $result)
    {
    //写入缓存
        foreach ($result as $field => $value) {
            if (!in_array($field)) {
                $value = str_replace('"', '', text($value));
            }

            $result[$field] = $value;
            $this->write_query_expand_cache($id, $field, str_replace('"', '', $value));
        }
        return $result;
    }


}