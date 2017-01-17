<?php
/**
     * APP discovery json接口
*/
namespace Api\Controller;

use Think\Controller\RestController;


class WeiboController extends RestController {
    protected $allowMethod    = array('get','post','put'); // REST允许的请求类型列表
    protected $allowType      = array('html','xml','json'); // REST允许请求的资源类型列表
    
    protected $Model;
    protected $CategoryModel;

    function _initialize()
    {
        $this->Model = D('Api/Weibo');
    }
	//
    public function index($page=1,$r=6)
    {
        switch ($this->_method){
            case 'get': //get请求处理代码
                
                $uId = I('id',0,'intval');
				
                
                if($uId)
                { //默认输出全部分类内容
					$map['uid']=$uId;
                    $map['status']=1;
					$order='create_time desc';
					$totalCount=$this->Model->where($map)->count();
					
					if($totalCount){
						$data=$this->Model->where($map)->page($page,$r)->order($order)->select();
					}
					//dump($data);
                    foreach($data as &$val){
                        $val['user']=query_user(array('space_url','avatar32','nickname'),$val['uid']);
                    }
                    unset($val);
					
                    $info = '返回成功';
                }
                else
                {//默认输出全部分类内容
                    $map['status']=1;
					$order='create_time desc';
					$totalCount=$this->Model->where($map)->count();
					
					if($totalCount){
						$data=$this->Model->where($map)->page($page,$r)->order($order)->select();
					}
					//dump($data);
                    foreach($data as &$val){
                        $val['user']=query_user(array('space_url','avatar32','nickname'),$val['uid']);
                        //$val['Thumbnail'] = getThumbImageById($val['cover'],352,240);
                    }
                    unset($val);
					
                    $info = '返回成功';
                }
                
            break;
            case 'put':
                
                $result['info'] = 'PUT未定义';
            
            break;
            case 'post'://post请求处理代码
                         
                $result['info'] = 'PUT未定义';
            break;
        
        
        }
       // dump($data);
        $result['info'] = $info;
        $result['data'] = $data;
        $result['code'] = 200;
        $this->response($result,'json');
       
    }
    
}