<?php
use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;


class ArticleForm extends Form{
	
    public function initialize($entity = null, $options = array()){

        $title = new Text("title");
        $title->addValidator(new PresenceOf(array(
            'message' => '标题不能为空'
        )));
        $title->addValidator(new StringLength(array(
            'min' => 1,
            'messageMinimum' => '标题过于简短'
        )));
        $this->add($title);
        
		/*$content = new Textarea("content");
        $content->addValidator(new PresenceOf(array(
            'message' => '内容不能为空'
        )));
        $this->add($content);
        
        $language = new Select('language');
        $language->addValidator(new PresenceOf(array(
            'message' => '请选择语言'
        )));
        $this->add($language);
        
        $share = new Radio('share');
        $share->addValidator(new PresenceOf(array(
            'message' => '请选择是否分享'
        )));
        $this->add($share);
        
        $visibility = new Radio('visibility');
        $visibility->addValidator(new PresenceOf(array(
            'message' => '请选择是否可见'
        )));
        $this->add($visibility);
        
        $division_category_id = new Select('division_category_id');
        $division_category_id->addValidator(new PresenceOf(array(
            'message' => '请选择分类'
        )));
        $this->add($division_category_id);*/
    }
}
?>