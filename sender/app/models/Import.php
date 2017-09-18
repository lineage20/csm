<?php

class Import extends \Phalcon\Mvc\Model
{
    public function initialize(){
       $this->belongsTo("group_id", "Group", "id");
       Import::skipAttributes(array('status','succeed','ignore','failed','ctime','mtime'));
    }
    static function getList($modelsManager , $where , $appendix = null ){



            $num = isset($appendix['pageSize'])  ? $appendix['pageSize'] : 25;
            $page = isset($appendix['page']) ? $appendix['page'] : 1;

            $builder = $modelsManager->createBuilder()
                   ->columns('Import.id as id,Import.file as file,Import.status as status,Import.succeed as succeed,Import.ignore as ignore,Import.failed as failed,Import.ctime as ctime,Import.mtime as mtime ,name')
                   ->from('Import')
                   ->leftjoin('Group')
                   ->orderby('id desc');
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

