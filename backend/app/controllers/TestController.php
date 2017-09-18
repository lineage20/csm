<?php

class TestController extends ControllerBase
{

    public function indexAction()
    {
    	echo phpinfo();die;
    	/*$divisionCategory = Category::find(
            "division_id = 3"
        );
        $cates_arr = $this->objToArray->ohYeah($divisionCategory);
		$items = $this->tree->_tree($cates_arr, $parent_id = 0, $level = 0);
		echo '<pre>';
		print_r($items);
        //$this->view->divisionCategory = $divisionCategory;
        exit;
	echo "hello";
	var_dump(array(1, 2, 3, 4));
	var_export(new stdclass());	*/
    }

}

