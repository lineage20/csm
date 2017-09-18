<?php 
class Template extends \Phalcon\Mvc\Model
{
	static public function initialize()
	{
	}
	static function insert($params = null){
		$t = new Template;
		$t->id = 0;
		if(is_array($params)){
			foreach($params as $k=>$v){
				$t->$k = $v;
			}
		}
		if($t->save()){
			return $t->id;
		}
		
		return false;
		
	}
	static function getList($where , $appendix = null ){
		
		/**
		$builder = $this->modelsManager->createBuilder()
		->columns('id, name')
		->from('Robots')
		->orderBy('name');
		
		$paginator = new Paginator(array(
				"builder" => $builder,
				"limit"=> 10,
				"page" => 1
		));
		**/
		
		
		$num = isset($appendix['pageSize'])  ? $appendix['pageSize'] : 10;
		$page = isset($appendix['page']) ? $appendix['page'] : 1;
		
		$data =  new \Phalcon\Paginator\Adapter\Model(
				array(
						"data" => Template::find(array($where , 'limit'=>$num,'offset'=>($page-1)*$num)),
						"count"=> Template::count(),
						"limit"=> $num,
						"page" => $page
				)
		);
		return $data;
		//var_dump($data);
		//exit();
		
		
	}
	static function defaultObject(){
		$obj = new stdClass;
		$columns = array('id'=>0,'category_id','name','decription','content','status','engine');
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