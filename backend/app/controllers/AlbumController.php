<?php

class AlbumController extends ControllerBase
{
    public function initialize() {
        parent::initialize();
    }

    public function indexAction()
    {
	
    }
    public function get($folder, $filename){
		
			//$filename ='test.jpg';
			$grid = $this->mongodb->db->getGridFS($folder);
			//echo $grid->storeFile($filename, array("date" => new MongoDate()));
           $image = $grid->findOne($filename);
		if ($image) {
			$image_file = sprintf("%s/static/image/%s", $this->basedir, $filename);
			$content = $image->getBytes();
			if(!is_dir(dirname($image_file))){
    			mkdir(dirname($image_file), 0755, TRUE);
    		}
			file_put_contents($image_file , $content);

	    	$this->response->setHeader('Cache-Control', 'max-age=60');
			$this->response->setHeader('Content-type', mime_content_type($image_file));
			$this->response->setContent($content);
			echo $content;
			
		}else{
			$this->response->setStatusCode(404, 'Image Not Found');
			$this->response->setContent('Image Not Found');
		}
		return($this->response);
	}
    public function imageAction($folder, $filename, $ver = 0){
    
    	//$filename = intval($filename);
    	$ver = intval($ver);
    	
		
		$this->get($folder, $filename);
                $this->view->disable();
		
    }
    
    
    public function folderAction($page_num = 1){
    	
        $currentPage = (int)$page_num;
         $folder = Album::find();
        $paginator = new Phalcon\Paginator\Adapter\Model(
        array(
                "data" => $folder,
                "limit"=> 3,
                "page" => $currentPage
            )
        );
        $page = $paginator->getPaginate();
       
        $this->view->setVar('page',$page);

    }
    public function addAction(){
        
    }
    public function addHandleAction(){
        $setoff = $this->request->getPost('setoff');
        if($setoff == "setoff"){
            return $this->response->redirect("folder");
        }
        if (!$this->request->isPost()) {
            return $this->response->redirect("folder");
        }
        $form = new AlbumForm();
        
        $album = new Album();
        $album->name = $this->request->getPost('name');
        $album->folder = $this->request->getPost('folder');
        $album->description = $this->request->getPost('description') != '' ? $this->request->getPost('description') : null;
//        echo $album->name.'-'.$album->folder.'-'.$album->description.'-'.$form->isValid($_POST);die;
        if (!$form->isValid($_POST)) {
            foreach ($form->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('add');
        }
        if ($album->save() == false) {
            echo json_encode(array('status'=>false,'msg'=> '添加文件夹失败'));
            exit;
        }else{
            echo json_encode(array('status'=>false,'msg'=> '添加文件夹成功'));
            exit;
        }
        exit;
    }
    /**
     * 展示图片
     */
    public function browseAction($folder,$skip){

        $grid = $this->mongodb->db->getGridFS($folder);
        $skip_num = 3*($skip-1);
        if($skip == ''){
            $skip_num = 0;
        }
        $image = $grid->find()->limit(3)->skip($skip_num);
        $count = $grid->count();

        $this->view->setVar('folder',$folder);
        $this->view->setVar('count',$count);
        $this->view->setVar('skip',$skip);
        $this->view->setVar('image',$image);
    }
    public function uploadAction(){
    	
        $folder = Album::find();
        $this->view->setVar('folder',$folder);
    }
    public function uploadHandleAction(){
        $folder = $this->request->getPost('folder');
       
        if($folder){
            $rs = $this->mongodb->upload($this->request , $folder);
            ?>
            <script>window.parent.showmodel(<?php echo json_encode($rs)?>);</script>
            <?php 
            exit();
    	}
    }
}

