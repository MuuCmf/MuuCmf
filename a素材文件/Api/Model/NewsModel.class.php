<?php
/**
 */

namespace Api\Model;


use Think\Model;

class NewsModel extends Model{

    public function editData($data)
    {
        if(!mb_strlen($data['description'],'utf-8')){
            $data['description']=msubstr(op_t($data['content']),0,200);
        }
        $detail['content']=$data['content'];
        $detail['template']=$data['template'];
        $data['reason']='';
        if($data['id']){
            $data['update_time']=time();
            $res=$this->save($data);
            $detail['news_id']=$data['id'];
        }else{
            $data['create_time']=$data['update_time']=time();
            $res=$this->add($data);
            action_log('add_news', 'News', $res, is_login());
            $detail['news_id']=$res;
        }
        if($res){
            D('News/NewsDetail')->editData($detail);
        }
        return $res;
    }

    public function getListByPage($map,$page=1,$order='update_time desc',$field='*',$r=20)
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

    public function getData($id)
    {
        if($id>0){
            $map['id']=$id;
            $data=$this->where($map)->find();
            if($data){
                $data['detail']=D('Api/NewsDetail')->getData($id);
            }
			$data['detail']['content'] = D('Api/News')->limgTohimg($data['detail']['content']);
            return $data;
        }
        return null;
    }
	
	public function limgTohimg($data){//替换内容中本地图片地址为http://结构
		$thisData = str_replace('src="/', 'src="http://v2.hoomuu.cn/', $data);
		
		return $thisData;
	}

    

} 