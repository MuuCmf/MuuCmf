<?php
/**
     * APP News json接口
*/
namespace Api\Controller;

use Think\Controller\RestController;


class NewsController extends RestController {
    protected $allowMethod    = array('get','post','put'); // REST允许的请求类型列表
    protected $allowType      = array('html','xml','json'); // REST允许请求的资源类型列表
    
    protected $newsModel;
    protected $newsCategoryModel;
	protected $newsDetailModel;

    function _initialize()
    {
        $this->newsModel = D('Api/News');
        $this->newsCategoryModel = D('Api/NewsCategory');
		$this->newsDetailModel = D('Api/NewsDetail');
    }
	//
    public function index($page=1,$r=6)
    {
        switch ($this->_method){
            case 'get': //get请求处理代码
                
                $aId = I('id',0,'intval');
                $category = I('category',0,'intval');
                
                if($aId)
                { //如果有ID，输出ID内容详细
                    if (!($aId && is_numeric($aId))) {
                        $info = 'ID错误';
                    }else{
                    $data=$this->newsModel->getData($aId);
					//$data['content'] = $this->newsDetailModel->getData($data['id']);
                    $data['author']=query_user(array('uid','space_url','nickname','avatar64','signature'),$data['uid']);
					$data['Thumbnail'] = getThumbImageById($data['cover'],352,240);
                    $data['user_news_count']=$this->newsModel->where(array('uid'=>$data['uid']))->count();
                    //dump($data);
                    $this->_category($data['category']);

                    /*获取上传文件路径*/
                    $file_id = $data['download'];
                    $downfile = D('file')->find($file_id);
                    //dump($downfile);
                    /* 更新浏览数 */
                    $map = array('id' => $aId);
                    $this->newsModel->where($map)->setInc('view');
                    $info = '返回成功';
                    }
                    
                }
                elseif($category)
                { //如果有分类ID，列出列表

                    $map['category']=$category;
                    $map['status']=1;
                    /* 获取当前分类下资讯列表 */
                    list($data,$totalCount) = $this->newsModel->getListByPage($map,$page,'sort desc,update_time desc','*',$r);
                    foreach($data as &$val){
                        $val['user']=query_user(array('space_url','avatar32','nickname'),$val['uid']);
                        $val['Thumbnail'] = getThumbImageById($val['cover'],352,240);
                    }
                    unset($val);
                    $info = '返回成功';
                    
                }//结束列表输出
                else
                {//默认输出全部分类内容
                    $map['status']=1;
                    list($data,$totalCount) = $this->newsModel->getListByPage($map,$page,'sort desc,update_time desc','*',$r);
                    foreach($data as &$val){
                        $val['user']=query_user(array('space_url','avatar32','nickname'),$val['uid']);
                        $val['Thumbnail'] = getThumbImageById($val['cover'],352,240);
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
    
    public function category()
    {
         switch ($this->_method){
            case 'get': //get请求处理代码
                
                $aId = I('id',0,'intval');

                if($aId)
                { //如果有ID，输出ID内容详细
                    if (!($aId && is_numeric($aId))) {
                        $info = 'ID错误';
                    }else{
                    $data=$this->newsCategoryModel->find($aId);
                    $info = '返回成功';
                    }
                    
                }
                else
                {//默认输出全部分类
                    $map['status']=1;
                    $data = $this->newsCategoryModel->where($map)->select();
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