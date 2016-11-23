<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-5-28
 * Time: 下午01:33
 * @author 郑钟良<zzl@ourstu.com>
 */

namespace Paper\Controller;


use Think\Controller;

class IndexController extends Controller{

    protected $paperModel;
    protected $paperCategoryModel;

    function _initialize()
    {
        $this->paperModel = D('Paper/Paper');
        $this->paperCategoryModel = D('Paper/PaperCategory');

        $catTitle=modC('PAPER_CATEGORY_TITLE','网站介绍','Paper');

        $sub_menu['left'][]= array('tab' => 'home', 'title' => $catTitle, 'href' =>  U('index'));
        $this->assign('sub_menu', $sub_menu);
        $this->assign('current','home');
    }

    public function index()
    {
        $catList=$this->paperCategoryModel->getCategoryList(array('status'=>1));
        if(count($catList)){
            $cat_ids=array_column($catList,'id');
            $catList=array_combine($cat_ids,$catList);
            $map['category']=array('in',array_merge($cat_ids,array(0)));
        }else{
            $map['category']=0;
            $catList=array();
        }
        $map['status']=1;
        $pageArtiles=$this->paperModel->getList($map,'id,title,sort,category,template');
        foreach($pageArtiles as $val){
            $val['type']='article';
            if($val['category']==0){
                $catList[]=$val;
            }else{
                $catList[$val['category']]['children'][]=$val;
            }
        }
        $catListSort=list_sort_by($catList,'sort');
        $this->assign('cat_list',$catListSort);

        $aId=I('id',0,'intval');
        if($aId==0){
            foreach($catList as $val){
                if($val['type']=='article'){
                    $aId=$val['id'];
                    break;
                }else{
                    if($val['children'][0]['id']){
                        $aId=$val['children'][0]['id'];
                        break;
                    }
                }
            }
        }

        if($aId){
            $pageArtiles=array_combine(array_column($pageArtiles,'id'),$pageArtiles);
            $contentTitle=$pageArtiles[$aId];
            $this->assign('content_title',$contentTitle);
            if($pageArtiles[$aId]['category']!=0){
                $cate=$catList[$pageArtiles[$aId]['category']];
                $this->assign('cate',$cate);
                $this->assign('top_id',$cate['id']);
            }else{
                $this->assign('top_id',0);
                $this->assign('id',$aId);
            }
        }
        $data=$this->paperModel->getData($aId);
        /* 获取模板 */
        if (!empty($data['template'])) { //已定制模板
            $tmpl = 'Index/'.$data['template'];
        } else { //使用默认模板
            $tmpl = 'Index/index';
        }
        $this->assign('data',$data);
        $this->display($tmpl);
    }

    public function feedBack() 
    {
        if(IS_POST){
            $aId = $_GET['id']; //获取当前文档ID
            $data['email']=I('post.email','','text');
            $data['content']=I('post.content','','text');
            $data['create_time']=time();

            // 自动验证规则
            $rules = array(
                array('email','require','email不能为空！',1),
                array('email','email','邮箱格式不正确！'),
                array('content','require','内容不能为空',1),
                array('content','20,500','内容必须大于20个字符',0,length),
            );
            $feedBack=M('feedback');
            // 创建数据对象
            if ($feedBack->validate($rules)->create($data)){
                // 创建数据对象成功，写入数据
                $result=$feedBack->add();
                if($result){
                    $this->success('反馈成功！',U('Paper/Index/index',array('id'=>$aId)));
                }else{
                    $this->error('反馈失败！');
                }
            }else{
                // 创建数据对象失败
                $this->error($feedBack->getError());
            }

        }else{
            $this->display();
        }

    }
} 