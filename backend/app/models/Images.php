<?php

class Images extends \Phalcon\Mvc\Model
{
	public function initialize(){
		//$this->hasOne('id', 'Category', 'category_id');
		$this->skipAttributes(array('ctime'));
	}
	
	public function beforeCreate(){
		//$this->ctime = date('Y-m-d H:i:s');
	}
}

?>