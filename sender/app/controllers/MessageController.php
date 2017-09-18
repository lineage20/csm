<?php
class MessageController extends ControllerBase {
	private $language = array('cn'=>'简体', 'tw'=>'繁体', 'en'=>'英语');
    public function initialize() {
        parent::initialize();
    }
    
	/**
	 * 默认页，显示文章列表
	 */
    public function indexAction(){
		$this->removeSearchSession();
		$this->listAction(1, 10);
		$this->view->partial('message/list');
    }
    
    /**
     * 文章列表
     * @param $page
     * @param $pageSize
     */
	public function listAction($page=1, $pageSize=10){
		$search_key = 'article_list_search';
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
//		$where['status'] = 'New';
		$appendix = array('page'=>$page, 'pageSize'=>$pageSize, 'order'=>'message.id desc');
		

		$list = Message::getList($this->modelsManager , $where , $appendix);
		$page = $list->getPaginate();
		$page->pageSize = $appendix['pageSize'];
		$this->view->page = $page;
	}
	
	/**
	 * 修改文章
	 * Enter description here ...
	 * @param int $id 文章ID
	 */
	public function editAction($id) {
        $parameters = array(
            "id = ?0",
            "bind" => array($id)
        );
        $article = Message::findFirst($parameters);
//        print_r($this->request->isPost());die;
        if ($this->request->isPost()) {
            print_r($this->request->isPost());
            $article->id = $this->request->getPost('id');
            $article->title = $this->request->getPost('title', 'trim');
            $article->content = $this->request->getPost('content', 'trim');

            $article->type = $this->request->getPost('type', 'trim');
            $article->status = $this->request->getPost('status', 'trim');
            if ($article->save()) {
                $this->view->successMessage = $this->tipsToRedirect->modalSuccessTips('修改成功', '/message');
            } else {
                $this->view->errorMessage = $article->getMessages();
            }
        }
        $this->view->article = $article;
    }

    /**
	 * 判断是否有编辑文章内容
	 */
	public function isEditAction(){
		if($this->request->isPost()){
			$params = $this->request->getPost();
			$have_modify = false;
			if($params['id']){
				$orgInfo = Message::findFirst($params['id']);
				foreach($params as $k=>$v){
					if(isset($orgInfo->$k) && $orgInfo->$k !== $v){
						$have_modify = true;
						break;
					}
				}
                                
                                
			}
			if($params['id']>0 && $have_modify==false){
				echo json_encode(array('status'=>true,'msg'=>'你还没修改'));
				$this->view->disable();
				return ;
			}    
		}
		exit;
	}
	/**
	 * 添加内容
	 */
	public function createAction($cateId = '') {
        if ($this->request->isPost()) {
            $article = new Message();
            $article->title = $this->request->getPost('title', 'trim');
            $article->content = $this->request->getPost('content', 'trim');
            $article->type = $this->request->getPost('type', 'trim');
            $article->status = 'New';
            if ($article->save()) {
                $this->view->successMessage = $this->tipsToRedirect->modalSuccessTips('添加成功', '/message');
            } else {
                $this->view->errorMessage = $article->getMessages();
            }
        }

        $this->view->cateId = $cateId;
    }

    /**
	 * 文章分类迁移
	 * Enter description here ...
	 */
	public function moveAction(){ 
		if($this->request->isPost()){
			$newVal['division_category_id'] = $this->request->getPost('new_division_category_id');
			$where['division_category_id'] = $this->request->getPost('ori_division_category_id');
			$status = Article::modifyArticle($this->modelsManager, $newVal, $where);
			if($status->success() == true){
				//$this->flash->error('文章分类迁移成功');
				$this->view->successMessage = $this->tipsToRedirect->modalSuccessTips('文章分类迁移成功', '/article');
			}
			else{
				/*foreach ($status->getMessages() as $message) {
		            $this->flash->error($message->getMessage());//$errors[] = $message->getMessage();
		        }*/
				$this->view->errorMessage = $status->getMessages();
			}
		}
		$divisionCategory = $this->getDivisionCategory();
        $this->view->divisionCategory = $divisionCategory;
	}
	
	/**
	 * 删除文章
	 */
	public function deleteAction(){
		if($this->request->isAjax()){
			$ids = $this->request->getPost('ids', 'trim');
			$act = $this->request->getPost('act', 'trim');
			if(!empty($ids)){
				$status = Article::deleteArticle($this->modelsManager, $ids, $act);
				$response = new Phalcon\Http\Response();
				if ($status->success() == true) {
			        $response->setJsonContent(array('status' => true, 'message'=>($act=='delete'?'删除成功':'修改成功')));
			    } else {
			        //Change the HTTP status
			        $response->setStatusCode(409, "Conflict");
			        $errors = array();
			        foreach ($status->getMessages() as $message) {
			            $errors[] = $message->getMessage();
			        }
			        $response->setJsonContent(array('status' => false, 'messages' => $errors));
			    }
			    return $response;
			}
		}
	}
	
	/**
	 * 上传图片
	 * @param int $articleId 文章ID
	 * @param string $oriPath 原图片路径
	 */
	private function uploadImage($articleId, $oriPath = ''){
		$image = '';
		$savePath = $this->imagesPath.$articleId.'/';//dirname(dirname(dirname(dirname(__FILE__)))).'/images/'.$articleId.'/';
		$returnPage = 'images/'.$articleId.'/';
    	if(php_uname('s')=='Windows NT'){
    		$savePath = dirname($_SERVER["DOCUMENT_ROOT"]).'/images/'.$articleId.'/';
    	}
		if(!file_exists($savePath)){
			mkdir($savePath, 0777, true);
		}
		//Check if the user has uploaded files
		if ($this->request->hasFiles() == true) {
			//Print the real file names and their sizes
			foreach ($this->request->getUploadedFiles() as $file){
				//echo $file->getName(), " ", $file->getSize(), "\n";
				if($file->isUploadedFile()){
					if($file->moveTo($savePath.$file->getName())){
						$image = $returnPage.$file->getName();
						if($oriPath!=''){
							unlink($savePath.$oriPath);
						}
					}
					else{
						//echo 'false';
					}
				}
				else{
					//echo 'can not upload';
				}
			}
		}
		else{
			if($oriPath){
				$image = $oriPath;
			}
		}
		return $image;
	}
	
	/**
	 * 获取事业部分类
	 * Enter description here ...
	 */
	private function getDivisionCategory(){
		//$this->divisionId = Division::getID();
		return $divisionCategory = Category::find(
            "division_id = {$this->Division_id}"
        );
	}
	
	/**
	 * 移除提交的查询条件
	 * Enter description here ...
	 */
	private function removeSearchSession(){
		$search_key = 'article_list_search';
		$this->session->remove($search_key);
	}
}