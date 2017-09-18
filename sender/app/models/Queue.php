<?php

class Queue extends \Phalcon\Mvc\Model
{
    public function initialize(){
        $this->belongsTo("contact_id", "Contact", "id");
        $this->belongsTo("task_id", "Task", "id");
    }
    static function getList($modelsManager , $where , $appendix = null ){
		
		

            $num = isset($appendix['pageSize'])  ? $appendix['pageSize'] : 25;
            $page = isset($appendix['page']) ? $appendix['page'] : 1;
            
            $builder = $modelsManager->createBuilder()
                   ->columns('Queue.task_id as task_id,Queue.contact_id as contact_id,Queue.status as status,Queue.ctime as ctime,Queue.mtime as mtime,Contact.name as name,Task.name as tname')
                   ->from('Queue')
                    ->leftjoin('Contact')
                    ->leftjoin('Task')
                    ->orderBy('mtime desc');
            $strWhere = null;
            if($where){
                    foreach($where as $k=>$v){
                        $strWhere[]  =  "{$k} = '{$v}'";    
                    }
                    $strWhere = implode(' AND ', $strWhere);
            }
            $builder =$builder->where($strWhere);

            $data =  new Phalcon\Paginator\Adapter\QueryBuilder(
                            array(
                                            "builder" => $builder,
                                            "limit"=> $num,
                                            "page" => $page
                            )
            );
            return $data;


    }
}

