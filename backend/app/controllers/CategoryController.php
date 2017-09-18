<?php
class CategoryController extends ControllerBase
{
    private $language = array('cn'=>'简体', 'tw'=>'繁体', 'en'=>'英语');
    public function indexAction(){
        $search_key = 'category_list_search';
//        $this->session->remove($search_key);
//        $this->listAction();
	
		if($this->request->isPost()){
			$params = $this->request->getPost();
			$this->session->set($search_key, $params);
		}
		$where = array();
		if($this->session->has($search_key)){
			$where = $this->session->get($search_key);
			$this->view->where  = $where;
			foreach($where as $k=>$v){
				if(empty($v)){
					unset($where[$k]);
				}
			}
		}
                $where['division_id'] = $this->Division_id;
                if($where){
			foreach($where as $k=>$v){
				if($k=='language'){
					$strWhere[]  =  "Category.{$k} = '{$v}'";
                                }else{
                                    $strWhere[]  =  "Category.{$k} = '{$v}'";
                                }
			}
			$strWhere = implode(' AND ', $strWhere);
		}
        $category = Category::find(
            "{$strWhere}"
        );
        $this->view->setVar('pages',$category);
        $this->view->setVar('language',$this->language);
    }
    
//    public function listAction(){
//        $search_key = 'category_list_search';
//        if($this->request->isPost()){
//			$params = $this->request->getPost();
//			$this->session->set($search_key, $params);
//		}
//		$where = array();
//		if($this->session->has($search_key)){
//			$where = $this->session->get($search_key);
//			$this->view->where  = $where;
//			foreach($where as $k=>$v){
//				if(empty($v)){
//					unset($where[$k]);
//				}
//			}
//		}
//                $where['division_id'] = $this->Division_id;
//                if($where){
//			foreach($where as $k=>$v){
//				if($k=='language'){
//					$strWhere[]  =  "Category.{$k} = '{$v}'";
//                                }else{
//                                    $strWhere[]  =  "Category.{$k} = '{$v}'";
//                                }
//			}
//			$strWhere = implode(' AND ', $strWhere);
//		}
//        $category = Category::find(
//            "{$strWhere}"
//        );
//        $this->view->setVar('pages',$category);
//        $this->view->setVar('language',$this->language);
//        $this->view->partial('category/index');
//    }

    //编辑分类页面
    public function editAction($id){
        $Division_id = $this->Division_id;
        $cates = Category::findFirst(
            "id = '{$id}'"
        );
        $category = Category::find(
            "division_id = '{$Division_id}' and language = '{$cates->language}'"
        );
        $this->view->setVar('pages',$category);
        $this->view->setVar('cates',$cates);
    }
    //添加分类页面
    public function addAction($child_id = 0){
        $id = $this->Division_id;
        if($child_id){
            $cates = Category::findFirst(
                "id = '{$child_id}'"
            );
        }
        if($cates){
            $category = Category::find(
                "division_id = '{$id}' and language = '{$cates->language}'"
            );
        }else{
            $category = Category::find(
                "division_id = '{$id}'"
            );
        }
        
        
        
        $this->view->setVar('cates',$cates);
        $this->view->setVar('pages',$category);
    }
    //查看分类
    public function showAction($id){
        $category = Category::findFirst(
            "id = '{$id}'"
        );
        $this->view->setVar('cates',$category);
    }
    //处理编辑分类
    public function editHandleAction(){
        $setoff = $this->request->getPost('setoff');
        if($setoff == "setoff"){
            return $this->response->redirect("index");
        }
        $Division_id = $this->Division_id;
        if (!$this->request->isPost()) {
            return $this->response->redirect("index");
        }
        $id = $this->request->getPost('id'); 
        $category = Category::findFirstById($id);
        if($this->request->getPost('language') == '简体'){
            $language = 'cn';
        }elseif($this->request->getPost('language') == '繁体'){
            $language = 'tw';
        }else{
            $language = 'en';
        }
        if($category->name == $this->request->getPost('name') && $category->visibility == $this->request->getPost('visibility') && $category->language == $language && $category->description == $this->request->getPost('description') && $category->parent_id == $this->request->getPost('parent_id')){
            echo json_encode(array('status'=>false,'msg'=> '未做任何修改'));
            exit;
        }
        if(!$category){
            $this->flash->error("Category does not exist");
            return $this->response->redirect("index");
        }
        $form = new CategoryForm();
        $category->name = $this->request->getPost('name');
        $category->division_id = $Division_id;
        $category->visibility = $this->request->getPost('visibility');
        //$category->mtime = date("Y-m-d H:i:s",  time());
        $category->parent_id = $this->request->getPost('parent_id');
        $category->description = $this->request->getPost('description');
        if($this->request->getPost('language') == '简体'){
            $category->language = 'cn';
        }elseif($this->request->getPost('language') == '繁体'){
            $category->language = 'tw';
        }else{
            $category->language = 'en';
        }
         if (!$form->isValid($_POST)) {
            foreach ($form->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('edit');
        }
        if ($category->save() == false) {
            echo json_encode(array('status'=>false,'msg'=> '修改分类失败'));
            exit;
        }else{
            echo json_encode(array('status'=>false,'msg'=> '修改分类成功'));
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
        $Division_id = $this->Division_id;
        if (!$this->request->isPost()) {
            return $this->response->redirect("index");
        }
        $form = new CategoryForm();
        
        $category = new Category();
        $category->name = $this->request->getPost('name');
        $category->division_id = $Division_id;
        $category->visibility = $this->request->getPost('visibility'); 
        $category->language = $this->request->getPost('hd_language') != '' ? $this->request->getPost('hd_language') : $this->request->getPost('language'); 
        $category->path = '/';
        $category->status = 'Enabled';
        if($this->request->getPost('parent_id') == 'NULL'){
            $category->parent_id = null;
        }else{
            $category->parent_id = $this->request->getPost('parent_id');
        }
        $category->description = $this->request->getPost('description') != '' ? $this->request->getPost('description') : null;
        if (!$form->isValid($_POST)) {
            foreach ($form->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('add');
        }
        if ($category->save() == false) {
            echo json_encode(array('status'=>false,'msg'=> '添加分类失败'));
            exit;
        }else{
            echo json_encode(array('status'=>false,'msg'=> '添加分类成功'));
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

