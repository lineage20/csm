<?php
class TaskController extends ControllerBase
{
    public function indexAction(){
        $datas = Task::find(array(
            "order" => "id desc"
        ));
        $this->view->setVar('datas', $datas);
    }
    //添加任务
    public function addAction(){
        $group_ids = Group::find();
        $init_message_ids = Message::find("type = 'Email' and status = 'New'");
        $init_template_ids = Template::find("type = 'Email' and status = 'Enabled'");


        $this->view->setVar('group_ids', $group_ids);
        $this->view->setVar('init_message_ids', $init_message_ids);
        $this->view->setVar('init_template_ids', $init_template_ids);

    }
    public function addHandleAction(){
        $setoff = $this->request->getPost('setoff');
        if($setoff == "setoff"){
            return $this->response->redirect("index");
        }
        if (!$this->request->isPost()) {
            return $this->response->redirect("index");
        }
        $form = new TaskForm();
        if (!$form->isValid($_POST)) {
            foreach ($form->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('add');
        }
        $exits_task = Task::findFirst(
           " name = '{$this->request->getPost('name')}'"
        );
        if(!empty($exits_task)){
            echo json_encode(array('status'=>false,'msg'=> '任务名称不能重复'));
            exit;
        }
        $task = new Task();
        $task->name = $this->request->getPost('name');
        $task->type = $this->request->getPost('type');
        $task->group_id = $this->request->getPost('group_id') != ''? $this->request->getPost('group_id') : null;
        $task->template_id = $this->request->getPost('template_id');
        $task->message_id = $this->request->getPost('message_id');
        $task->status = 'New';
        if ($task->save() == false) {
            echo json_encode(array('status'=>false,'msg'=> '添加任务失败'));
            exit;
        }else{
            echo json_encode(array('status'=>false,'msg'=> '添加任务成功'));
            exit;
        }
        exit;
    }
    public function deleteAction($id) {
        if ($id) {
            $status = false;
            $task = new Task();
            foreach ($task->find("id='{$id}'") as $item) {
                if ($item->delete() == false) {
                    $status = true;
                } else {

                }
            }
            if ($status == false) {
                return $this->response->redirect("/task/index");
            }
            echo '删除失败';
        }
    }

    //获取不同类型的message_id,template_id
    public function getIdsAction(){
        if (!$this->request->isPost()) {
            return $this->response->redirect("add");
        }
        $type = $this->request->getPost('type');
        $message_ids = Message::find("type = '{$type}'");
        $template_ids = Template::find("type = '{$type}'");

        echo json_encode(array(
            'status'=>"true",
            'message_ids'=>$this->objToArray->ohYeah($message_ids),
            'template_ids'=>$this->objToArray->ohYeah($template_ids),
        )) ;
        exit;
    }

    public function getDatasAction(){
        if (!$this->request->isPost()) {
            return $this->response->redirect("add");
        }
        $message_ids = $this->request->getPost('message_ids');
        $template_ids = $this->request->getPost('template_ids');

        $this->view->setVar('change_message_ids', $message_ids);
        $this->view->setVar('change_template_ids', $template_ids);
        exit;
    }
    //导入联系人
    public function uploadAction($format = null,$page = 1 , $pageSize = 25){
        $appendix = array('page'=>$page,'pageSize'=>$pageSize);
        $where = array();
        $list = Import::getList($this->modelsManager , $where , $appendix);

        $page = $list->getPaginate();

        $page->pageSize = $appendix['pageSize'];
        $this->view->page = $page;
        if($format == 'example'){
            $file = '../images/contact.list.import.csv';
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);
            exit;
        }
    }
    public function uploadDealAction($group_id){
        if ($this->request->hasFiles() == true) {
            // Print the real file names and sizes
            foreach ($this->request->getUploadedFiles() as $file) {
                if($file->getType() != 'application/vnd.ms-excel' && $file->getType() != 'application/octet-stream'){
                    echo "文件格式不正确";
                    die();
                }
                //Move the file into the application
                $basedir = '/www/hx9999.com/edm.hx9999.com';
                $image_path = sprintf("%s/tmp/sender/%s", $basedir, $file->getName());
                if(!is_dir(dirname($image_path))){
                    mkdir(dirname($image_path), 0755, TRUE);
    		}
                if(php_uname('s')=='Windows NT'){//本地测试时使用
                    $image_path = dirname($_SERVER["DOCUMENT_ROOT"]).'/images/'.$file->getName();
                }
                $file->moveTo($image_path);
                if (file_exists($image_path)) {
                    $import = new Import();
                    $import->group_id = $group_id;
                    $import->file = $image_path;
                    $import->save();
                    if(isset($import->id)){
                       echo "导入成功";
                       die();
                    }else{
                       echo "Sorry,导入失败";
                       die();
                    }
                } else {
                    echo "Sorry,导入失败";
                    die();
                }
            }
        }
    }
}

