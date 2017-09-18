<?php
class ContactController extends ControllerBase
{
    public function indexAction(){
        $search_key = 'template_list_search';
        $this->session->remove($search_key);
        $this->listAction(1,25);
        $this->view->partial('contact/list');


    }
    public function listAction($page = 1 , $pageSize = 25){
        $search_key = 'template_list_search';
        $dbkey = $this->config->database->key;
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

            $group_id = isset($where['group_id']) ? $where['group_id'] : '';

            $appendix = array('page'=>$page,'pageSize'=>$pageSize);
            $contact = new Contact();
            $list = $contact->getList($this->modelsManager , $where , $appendix,$dbkey);
            $list = $this->objToArray->ohYeah($list);
            $data['pages'] = $contact->paginator($group_id,$pageSize, $page);
            $groupId = Group::find();
            $this->view->builder = $list['builder'];
            $this->view->pages = $data['pages'];
            $this->view->groupId = $groupId;
    }
    //导入联系人
    public function uploadAction($format){
        if($format == 1){
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

    public function uploadHandleAction($group_id){
        ini_set("max_execution_time", "120");
        $new_data = array();
        $dbkey = $this->config->database->key;
        $mb = 4; //Mb
        $maxFileSize = $mb * 1024 * 1024;
        if ($_FILES['file']) {
            $failed_username_arr = array();
            $file = (object) $_FILES['file'];
            if ($file->error == 0) {
                if (file_exists($file->tmp_name)) {
                    // 文件扩展名检查
                    $ext = preg_replace("/.*\.([^\.]+)$/", "\$1", $file->name);
                    $ext = strtolower($ext);
                    // 文件大小检查
                    if ($file->size > $maxFileSize) {
                        echo "'Sorry,只能上传文件大小为'.$mb.'MB以内的文件(当前文件大小为<u>' .
                        round($file->size/1024/1024,2) . 'MB</u>)'";
                        die();
                    }
                    //end
                    $column = 3; //csv列数
                    $row = 0;
                    $sum = 0; //总金额
                    $handle = fopen($file->tmp_name, "r");
                    $dataArray = array();
                    while (($filedata = fgetcsv($handle, 100000, ",")) !== false) {
                        $num = count($filedata);
                        if ($row == 0) {
                            $row++;
                            continue;
                        }
                        $encode = mb_detect_encoding($filedata[0], array("ASCII",'UTF-8','GB2312',"GBK",'BIG5'));
                        $filedata[0] = mb_convert_encoding($filedata[0], 'UTF-8',"GB2312,GBK,GB18030,BIG5");
                        $dataArray[] = $filedata;
                    }
                    fclose($handle);

                    $contact_model = new Contact();
                    if (!empty($dataArray)) {
                        foreach ($dataArray as $key => $vs) {
                            $time = date('Y-m-d H:i:s', time());
                            $groups = $contact_model->findGroupByMobileOrEmail($dbkey, $vs,$action = 'upload');
                            if(in_array($group_id,$groups)){
                                continue;
                            }
                            $contact = Contact::findFirst(
                                "AES_DECRYPT(mobile,'{$dbkey}') = '{$vs[1]}' or AES_DECRYPT(email,'{$dbkey}') = '{$vs[2]}' or mobile_digest = md5('{$vs[1]}') or email_digest = md5('{$vs[2]}')"
                            );
                            if(!empty($contact)){
                                $insertId = $contact->id;
                            }else{
                                $phql = "INSERT INTO contact (name, mobile, email, mobile_digest, email_digest, status, ctime) VALUES ('{$vs[0]}', AES_ENCRYPT('{$vs[1]}','{$dbkey}'), AES_ENCRYPT('{$vs[2]}','{$dbkey}'), md5('{$vs[1]}'), md5('{$vs[2]}'), 'Subscription', '{$time}')";
    //                        echo $phql;die;
                                $result = $this->modelsManager->executeQuery($phql);
                                if ($result->success() == false) {
                                    foreach ($result->getMessages() as $message) {
                                        echo $message->getMessage();
                                    }
                                }
                                $insertId = $contact_model->getWriteConnection()->lastInsertId($contact_model->getSource());
                            }
                        }
                        if(isset($insertId)){
                            $groupHasContact = new GroupHasContact();
                            $groupHasContact->group_id = $group_id;
                            $groupHasContact->contact_id = $insertId;
                            $groupHasContact->save();
                            if(isset($groupHasContact->id)){
                                echo "成功导入";
                                die();
                            }
                        }else{
                            echo "Sorry,导入失败,请检查手机号码或邮箱是否重复";
                            die();
                        }
                    } else {
                        echo "Sorry,文件中的数据不合法3";
                        die();
                    }
                } else {
                    echo "Sorry,文件中的数据不合法3";
                    die();
                }
            }
        }

        $this->view->disable();
    }
    public function addAction(){

    }
    public function addHandleAction(){

        $dbkey = $this->config->database->key;
        $setoff = $this->request->getPost('setoff');
        if($setoff == "setoff"){
            return $this->response->redirect("index");
        }
        if (!$this->request->isPost()) {
            return $this->response->redirect("index");
        }

        $form = new ContactForm();
        $contact = new Contact();
        $datas = array();
        $datas['name'] = $this->request->getPost('name');
        $datas['email'] = $this->request->getPost('email');
        $datas['mobile'] = $this->request->getPost('mobile');
        $datas['description'] = $this->request->getPost('description') != '' ? $this->request->getPost('description') : null;
        $group_id = $this->request->getPost('type');
        if (!$form->isValid($_POST)) {
            foreach ($form->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('add');
        }

        $groups = $contact->findGroupByMobileOrEmail($dbkey, $datas,$action = 'add');
//        $groups_arr = $this->objToArray->ohYeah($groups);
//        print_r($groups);die;

        if(in_array($group_id,$groups)){
            echo json_encode(array('status'=>false,'msg'=> '添加的手机号或邮件在本组重复'));
            exit;
        }else{
            $contact = Contact::findFirst(
                "AES_DECRYPT(mobile,'{$dbkey}') = '{$datas['mobile']}' or AES_DECRYPT(email,'{$dbkey}') = '{$datas['email']}' or mobile_digest = md5('{$datas['mobile']}') or email_digest = md5('{$datas['email']}')"
            );
            if($contact->id){
                $insertId = $contact->id;
            }else{
                $phql = "INSERT INTO contact (name, mobile, email, mobile_digest, email_digest, description, status) VALUES ('{$datas['name']}', AES_ENCRYPT('{$datas['mobile']}','{$dbkey}'), AES_ENCRYPT('{$datas['email']}','{$dbkey}'), md5('{$datas['mobile']}'), md5('{$datas['email']}'), '{$datas['description']}', 'Subscription')";
                $result = $this->modelsManager->executeQuery($phql);
                if ($result->success() == false) {
                    foreach ($result->getMessages() as $message) {
                        echo $message->getMessage();
                    }
                }
                $insertId = $contact->getWriteConnection()->lastInsertId($contact->getSource());
            }
            if (isset($insertId)) {
                $groupHasContact = new GroupHasContact();
                $groupHasContact->group_id = $this->request->getPost('type');
                $groupHasContact->contact_id = $insertId;
                $groupHasContact->save();
                if(isset($groupHasContact->id)){
                   echo json_encode(array('status'=>false,'msg'=> '添加联系人成功'));
                   exit;
                }else{
                    echo json_encode(array('status'=>false,'msg'=> '添加联系人失败'));
                    exit;
                }
            }else{
                echo json_encode(array('status'=>false,'msg'=> '添加联系人失败'));
                exit;
            }
        }
        exit;
    }
    public function editAction($id){
        $dbkey = $this->config->database->key;
        $phql = "SELECT id,name,AES_DECRYPT(mobile,'{$dbkey}') as mobile,AES_DECRYPT(email,'{$dbkey}') as email,status,description FROM Contact where id = '{$id}'";
        $contact = $this->modelsManager->executeQuery($phql);
        $contact_arr = $this->objToArray->ohYeah($contact);
        $this->view->setVar('contact',$contact_arr);
    }
    public function editHandleAction(){
        $dbkey = $this->config->database->key;
        $setoff = $this->request->getPost('setoff');
        if($setoff == "setoff"){
            return $this->response->redirect("index");
        }
        if (!$this->request->isPost()) {
            return $this->response->redirect("index");
        }
        $exist_contact = Contact::findFirst(
                "(mobile_digest = md5('{$this->request->getPost('mobile')}') or email_digest = md5('{$this->request->getPost('email')}')) and id != '{$this->request->getPost('id')}'"
            );
        if(!empty($exist_contact)){
            echo json_encode(array('status'=>false,'msg'=> '修改联系人手机号码或邮箱不能重复'));
            exit;
        }
        $contact = new Contact();
        $id = $this->request->getPost('id');
        $datas['name'] = $this->request->getPost('name');
        $datas['email'] = $this->request->getPost('email');
        $datas['mobile'] = $this->request->getPost('mobile');
        $datas['status'] = $this->request->getPost('status');
        $datas['description'] = $this->request->getPost('description') != '' ? $this->request->getPost('description') : null;
        $datas['mobile_digest'] = md5($datas['mobile']);
        $datas['email_digest'] = md5($datas['email']);

        $phql = "SELECT id,name,AES_DECRYPT(mobile,'{$dbkey}') as mobile,AES_DECRYPT(email,'{$dbkey}') as email,status,description FROM Contact where id = '{$id}'";
        $contact_data = $this->modelsManager->executeQuery($phql);
        $contact_arr = $this->objToArray->ohYeah($contact_data);
        if($contact_arr[0]['name'] == $datas['name'] && $contact_arr[0]['description'] == $datas['description'] && $contact_arr[0]['mobile'] == $datas['mobile'] && $contact_arr[0]['email'] == $datas['email'] && $contact_arr[0]['status'] == $datas['status']){
            echo json_encode(array('status'=>false,'msg'=> '未做任何修改'));
            exit;
        }
        if(empty($contact_arr)){
            $this->flash->error("Contact does not exist");
            return $this->response->redirect("index");
        }
        $form = new ContactForm();

         if (!$form->isValid($_POST)) {

            foreach ($form->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('edit');
        }

        $phql = "UPDATE Contact SET name = '{$datas['name']}' , mobile = AES_ENCRYPT('{$datas['mobile']}','{$dbkey}') ,email = AES_ENCRYPT('{$datas['email']}','{$dbkey}'),"
        . "mobile_digest = '{$datas['mobile_digest']}',email_digest = '{$datas['email_digest']}',status = '{$datas['status']}',description = '{$datas['description']}' WHERE id = '{$id}'";


        $result = $this->modelsManager->executeQuery($phql);
        if ($result->success() == false) {
            echo json_encode(array('status'=>false,'msg'=> '修改联系人失败'));
            exit;
        }else{
            echo json_encode(array('status'=>false,'msg'=> '修改联系人成功'));
            exit;
        }




        exit;
    }
}

