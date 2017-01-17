<?php
/**
     * APP discovery json接口
*/
namespace Api\Controller;

use Think\Controller\RestController;


class DiscoveryController extends RestController {
    protected $allowMethod    = array('get','post','put'); // REST允许的请求类型列表
    protected $allowType      = array('html','xml','json'); // REST允许请求的资源类型列表
    
    protected $Model;
    protected $CategoryModel;

    function _initialize()
    {
        $this->Model = D('Api/Discovery');
        //$this->CategoryModel = D('Resources/ResourcesCategory');
    }
	//
    public function index($page=1,$r=6)
    {
        switch ($this->_method){
            case 'get': //get请求处理代码
                
                $aId = I('id',0,'intval');
                //$category = I('category',0,'intval');
                
                if($aId)
                { //如果有ID，输出ID内容详细
                    if (!($aId && is_numeric($aId))) {
                        $info = 'ID错误';
                    }else{
                    $data=$this->Model->getData($aId);
                    $data['author']=query_user(array('uid','space_url','nickname','avatar64','signature'),$data['uid']);
                    $data['discovery_count']=$this->Model->where(array('uid'=>$data['uid']))->count();
                    //$this->_category($data['category']);

                    /*获取上传文件路径*/
                    //$file_id = $data['download'];
                    //$downfile = D('file')->find($file_id);
                    //dump($downfile);
                    /* 更新浏览数 */
                    $map = array('id' => $aId);
                    $this->Model->where($map)->setInc('view');
                    $info = '返回成功';
                    }
                    
                }
                else
                {//默认输出全部分类内容
                    $map['status']=1;
                    list($data,$totalCount) = $this->Model->getListByPage($map,$page,'update_time desc','*',$r);
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