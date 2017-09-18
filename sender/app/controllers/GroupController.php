<?php
class GroupController extends ControllerBase
{
    public function indexAction(){

        $group = Group::find( );
        $this->view->setVar('group',$group);
    }
    


    //编辑分类页面
    public function editAction($id){
        $group = Group::findFirst(
            "id = '{$id}'"
        );
        $this->view->setVar('group',$group);
    }
    //添加分组页面
    public function addAction(){
        
    }
    //查看分类
    public function showAction($id){
        $category = Category::findFirst(
            "id = '{$id}'"
        );
        $this->view->setVar('cates',$category);
    }
    //处理编辑分组
    public function editHandleAction(){
        $setoff = $this->request->getPost('setoff');
        if($setoff == "setoff"){
            return $this->response->redirect("index");
        }
        if (!$this->request->isPost()) {
            return $this->response->redirect("index");
        }
        $id = $this->request->getPost('id'); 
        $group = Group::findFirstById($id);
        
        if($group->name == $this->request->getPost('name') && $group->description == $this->request->getPost('description')){
            echo json_encode(array('status'=>false,'msg'=> '未做任何修改'));
            exit;
        }
        if(!$group){
            $this->flash->error("Group does not exist");
            return $this->response->redirect("index");
        }
        $form = new GroupForm();
        $group->name = $this->request->getPost('name');
        $group->description = $this->request->getPost('description');
        
         if (!$form->isValid($_POST)) {
            foreach ($form->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('edit');
        }
        if ($group->save() == false) {
            echo json_encode(array('status'=>false,'msg'=> '修改分组失败'));
            exit;
        }else{
            echo json_encode(array('status'=>false,'msg'=> '修改分组成功'));
            exit;
        }
        exit;
        
    }
    //处理添加分类
    public function addHandleAction(){
        $setoff = $this->request->getPost('setoff');
        if($setoff == "setoff"){
            return $this->response->redirect("index");
        }
        if (!$this->request->isPost()) {
            return $this->response->redirect("index");
        }
        $form = new GroupForm();
        
        $group = new Group();
        $group->name = $this->request->getPost('name');
        $group->description = $this->request->getPost('description') != '' ? $this->request->getPost('description') : null;
        if (!$form->isValid($_POST)) {
            foreach ($form->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('add');
        }
        if ($group->save() == false) {
            echo json_encode(array('status'=>false,'msg'=> '添加分组失败'));
            exit;
        }else{
            echo json_encode(array('status'=>false,'msg'=> '添加分组成功'));
            exit;
        }
        exit;
    }
    //获取顶级分类的语言
    public function getLangAction(){
        if (!$this->request->isPost()) {
            return $this->response->redirect("index");
        }
        $id = $this->request->getPost('id'); 
        $category = Category::findFirstById($id);
        
        echo json_encode(array(
            'message'=>"操作成功",
            'data'=>$category
        )) ;
        exit;
    }
    
}

