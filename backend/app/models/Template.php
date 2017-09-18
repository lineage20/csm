<?php 
class Template extends \Phalcon\Mvc\Model
{
	public function initialize()
	{
		$this->skipAttributes(array('ctime','mtime'));
	}
	static function insert($params = null){
		
		$temp = new Template;
		if(is_array($params)){
			foreach($params as $k=>$v){
				$temp->$k = $v;
			}
		}
		if($temp->save()){
			return $temp->id;
		}else{
			return $temp->getMessages();
		}
	}
	static function getList($modelsManager , $where , $appendix = null ){
		$num = isset($appendix['pageSize'])  ? $appendix['pageSize'] : 10;
		$page = isset($appendix['page']) ? $appendix['page'] : 1;
		
		$builder = $modelsManager->createBuilder()
					->columns('template.*')
					->from('template')
					->orderBy('template.id DESC');
		$strWhere = null;
		if($where){
			foreach($where as $k=>$v){
				if(is_numeric($k)){
					$strWhere[]  =  $v;
				}else{
					if($k=='name'){
						$strWhere[]  =  "template.{$k} LIKE  '%{$v}%'";
					}else{
						$strWhere[]  =  "template.{$k} = '{$v}'";
					}
					
				}
			}
			$strWhere = implode(' AND ', $strWhere);
		}
		$builder =$builder->where($strWhere);
		
		return $paginator = new Phalcon\Paginator\Adapter\QueryBuilder(array(
			    "builder" => $builder,
			    "limit"=> $num,
				"page" => $page
			));
		
	}
	static function defaultObject($returnArr = false){

		$columns = array('id'=>0,'division_id'=>0,'name','decription','content','type','status'=>'Disabled','engine');
		if($returnArr === true ) return $columns;
		$obj = new stdClass;
		foreach($columns as $k=>$v){
			if(is_string($k)){
				$obj->{$k} = $v;
			}else{
				$obj->{$v} = '';
			}
		}
		return $obj;
	}
	
	
	
	
}
?>