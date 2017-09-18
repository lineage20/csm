<?php 
use Phalcon\Mvc\Model\Resultset\Simple as Resultset;

class Users extends \Phalcon\Mvc\Model
{
	public function getSource()
	{
		return 'users';
	}
	public function initialize()
	{
		$this->hasMany('id', 'Users_info', 'id');
	}
	public function getList()
	{
		$paginator = new \Phalcon\Paginator\Adapter\Model(
				array(
						"data" => Users::find(),
						"limit"=> 10,
						"page" => 1
				)
		);
		
		/** 第一种方法 **/
		$query = new \Phalcon\Mvc\Model\Query("SELECT * FROM users", $this->getDI());
		$cars = $query->execute();
		
		/** 第二种 **/
		$cars = Users::find();//->getFirst()
		
		/**  第三种  **/
		//$manager = new \Phalcon\Mvc\Model\Manager;
		//$builder = $manager->createBuilder();
		
		/**
		$builder->from('users')
		->join('users_info')
		->getQuery()
		->getSingleResult();
		**/
		$services = $this->getDI()->getServices();
		foreach($services as $key => $service) {
			//var_dump($key);
			//var_dump(get_class($this->getDI()->get($key)));
		}
		
		/** 第四种 **/
		$sql = "SELECT * FROM users";
		$users = new Users();
		$cars =  new Resultset(null, $users, $users->getReadConnection()->query($sql));
		//print_r($cars);
		
		$data = array();
		
		foreach($cars as $item){
			$data[]= array('id'=>$item->id,'name'=>$item->name);
		}
		print_R($data);
		
		
		
		
		
	}
}
?>