<?php

class Video extends \Phalcon\Mvc\Model
{
	public function initialize(){
		$this->hasOne("category_id", "Category", "id");//{0}主表关联子表的ID，{1}关联的子表，{2}子表ID
		$this->skipAttributes(array('from', 'ctime', 'mtime'));
	}
}
?>