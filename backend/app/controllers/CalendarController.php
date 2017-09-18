<?php

use Phalcon\Mvc\View;

class CalendarController extends \Phalcon\Mvc\Controller {

    private $request_url;
    private $http_code;
    private $result_code;
    private $http_msg;
    //接口属性
    private $platTypeKey;
    private $platAccount;
    private $lang;
    private $manager;

    public function initialize() {
        $this->request_url = 'http://handanapi.hx9999.com/api/zxFinanceData/list';
        $this->oauthKey = 'ade06a7ff83a0ce5';
        $this->platTypeKey = 'hengxin';
        $this->platAccount = 'hx';
        $this->timeStamp = time();
        $this->lang = 'zh';
        $this->token = md5($this->platAccount . $this->oauthKey . $this->timeStamp);
	$this->manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
    }
     public function indexAction() {
         $search_key = 'calendar_search';
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

        $this->session->remove($search_key);
         //print_r($params);die;
        $date = isset($params['bctime']) ? $params['bctime'] : date('Y-m-d');
        $listArr['token'] = $this->token;
        $listArr['timeStamp'] = $this->timeStamp;
        $listArr['platTypeKey'] = $this->platTypeKey;
        $listArr['platAccount'] = $this->platAccount;
        $listArr['lang'] = $this->lang;
        $listArr['siteflg'] = 1; // 站点
        $listArr['datestr'] = $date; // 站点
        
        $listStr = "?releaseTime=".$date."&dataTypeCon=1";

        $result = $this->cal_curl($this->request_url, $listStr, 'GET');

        //$result = $this->cal_curl($this->request_url . '/restweb/finance/index.json', $listArr, 'POST');
        $this->http_code = $result['status'];
        $cal_obj = json_decode($result['content'], true);
        $cal_arr = $cal_obj['finance']['financeDataList'];
    //    $db = $this->mongodb->db;
     //   $collection = $db->calendar;
     //   $mongo_data = $collection->find();

$filter = [];
$options = [];

$query = new MongoDB\Driver\Query($filter,$options);
$mongo_data = $this->manager->executeQuery('finance.calendar', $query);





        $new_arr = array();
        foreach($mongo_data as $obj){
//print_r($obj);die;
	    $obj = (array)$obj;
            $id_arr[] = $obj['id'];
            $new_arr[$obj['id']]['expect'] = $obj['expect'];
            $new_arr[$obj['id']]['actual'] = $obj['actual'];
            $new_arr[$obj['id']]['expect_text'] = $obj['expect_text'];
            $new_arr[$obj['id']]['actual_text'] = $obj['actual_text'];
        }
        foreach($cal_arr as $key=>$vs){
            $compare = time() - strtotime($date." ".$vs['fdTime']);
           // print_r($mongo_data);die;
            if($vs['fdTitle'] == ""|| ($compare>0 && $vs['actual'] == "--")){
                unset($cal_arr[$key]);
            }else{
                if(in_array($vs['externalId'], $id_arr)){
                    $cal_arr[$key]['expect_mongo'] = $new_arr[$vs['externalId']]['expect'];
                    $cal_arr[$key]['actual_mongo'] = $new_arr[$vs['externalId']]['actual'];
                    $cal_arr[$key]['expect_text_mongo'] = $new_arr[$vs['externalId']]['expect_text'];
                    $cal_arr[$key]['actual_text_mongo'] = $new_arr[$vs['externalId']]['actual_text'];
                }else{
                    $cal_arr[$key]['expect_mongo'] = "";
                    $cal_arr[$key]['actual_mongo'] = "";
                    $cal_arr[$key]['expect_text_mongo'] = "";
                    $cal_arr[$key]['actual_text_mongo'] = "";
                }
            }

        }
        $cal_arr = array_values($cal_arr);
//相同国家合并
        foreach($cal_arr as $k=>$v){
            $cal_arr_new[$v['fdTime'].$v['fdCountry']][$k] = $v;
        }
        $this->view->setVar('finance', $cal_arr_new);
     }

    public function jsonAction() {
        $params = $this->request->getPost();
        print_r($params);die;
        $date = isset($params['nowDate']) ? $params['nowDate'] : date('Y-m-d');
        $listArr['token'] = $this->token;
        $listArr['timeStamp'] = $this->timeStamp;
        $listArr['platTypeKey'] = $this->platTypeKey;
        $listArr['platAccount'] = $this->platAccount;
        $listArr['lang'] = $this->lang;
        $listArr['siteflg'] = 1; // 站点
        $listArr['datestr'] = $date; // 站点

        $result = $this->cal_curl($this->request_url . '/restweb/finance/index.json', $listArr, 'POST');
        $this->http_code = $result['status'];
        echo json_encode(json_decode($result['content'], true));
        exit();
    }

    private function cal_curl($url, $data = NULL, $method = 'GET') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            if (!empty($data)) {
                if (is_array($data)) {
                    $data = http_build_query($data);
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        } else {
            curl_setopt($ch, CURLOPT_URL, $url . $data);
        }
        $info['content'] = curl_exec($ch);
        $info['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($info['content'] === false) {
            $info['content'] = 'curl_error: ' . curl_error($ch);
        }
        curl_close($ch);
        return $info;
    }
//    public function dealmongodbAction(){
//        $params = $this->request->getPost();
//        $bctime = str_replace("-","",$params['bctime']);
//        $db = $this->mongodb->db;
//        $collection = $db->calendar;
//        $data = $collection->findOne(array('id' => $params['id']));
//
//        if(isset($data) && $data != ""){
//            $newdata = array('$set' => array('expect' =>$params['expect'], 'actual'=>$params['actual'], 'expect_text'=>$params['expect_text'], 'actual_text'=>$params['actual_text']));              // 修改
//            $collection->update(array("id" => $params['id']), $newdata);
//
//            $list=array("msg"=>"更新成功","status"=>"update");
//            echo json_encode($list);
//            exit();
//            //print_r($collection->findOne(array('id' => $params['id'])));
//
//        }else{
//            $data = array('id' =>$params['id'], 'expect' =>$params['expect'], 'actual'=>$params['actual'], 'expect_text'=>$params['expect_text'], 'actual_text'=>$params['actual_text']);
//            $collection->insert($data);
//            $list=array("msg"=>"插入成功","status"=>"insert");
//            echo json_encode($list);
//            exit();
//        }
//        die;
//
//    }
	public function dealmongodbAction(){
        $params = $this->request->getPost();
        $bctime = str_replace("-","",$params['bctime']);
//echo $params['id'];
        $filter = ['id' => $params['id']];
        $options = [];

        $query = new MongoDB\Driver\Query($filter,$options);
        $data = $this->manager->executeQuery('finance.calendar', $query);
//查询记录总数
	$commands = [
                'count' => "calendar",
                'query' => $filter
            ];
        $command = new \MongoDB\Driver\Command($commands);
        $cursor = $this->manager->executeCommand('finance', $command);
        $info = $cursor->toArray();
        $count = $info[0]->n;

      if(isset($count) && $count == 1){
          $bulk = new MongoDB\Driver\BulkWrite;
          $bulk->update(
              ['id' => $params['id']],
              ['$set' => ['expect' =>$params['expect'], 'actual'=>$params['actual'], 'expect_text'=>$params['expect_text'], 'actual_text'=>$params['actual_text']]],
              ['multi' => false, 'upsert' => false]
          );
          $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
          $result = $this->manager->executeBulkWrite('finance.calendar', $bulk, $writeConcern);
          
//           $newdata = array('$set' => array('expect' =>$params['expect'], 'actual'=>$params['actual'], 'expect_text'=>$params['expect_text'], 'actual_text'=>$params['actual_text']));              // 修改
//           $collection->update(array("id" => $params['id']), $newdata);

          $list=array("msg"=>"更新成功","status"=>"update");
          echo json_encode($list);
          exit();

      }else{
            $bulk = new MongoDB\Driver\BulkWrite;

            $data = ['id' =>$params['id'], 'expect' =>$params['expect'], 'actual'=>$params['actual'], 'expect_text'=>$params['expect_text'], 'actual_text'=>$params['actual_text']];

            $bulk->insert($data);
            $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
            $result = $this->manager->executeBulkWrite('finance.calendar', $bulk, $writeConcern);
//            $data = array('id' =>$params['id'], 'expect' =>$params['expect'], 'actual'=>$params['actual'], 'expect_text'=>$params['expect_text'], 'actual_text'=>$params['actual_text']);
//            $collection->insert($data);
            $list=array("msg"=>"插入成功","status"=>"insert");
            echo json_encode($list);
            exit();
        }
        die;
    }

}
