<?php

class TemplateController extends ControllerBase
{

	public function indexAction()
	{
		echo "hello";
		var_dump(array(1, 2, 3, 4));
		var_export(new stdclass());
	}
	public function flushAction($division_id,$category_id,$template_id){

	}
	public function purgeAction(){
	}
}