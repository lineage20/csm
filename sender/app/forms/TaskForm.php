<?php

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;


class TaskForm extends Form
{
    public function initialize($entity = null, $options = array())
    {

        $name = new Text("name");
        $name->addValidator(new PresenceOf(array(
            'message' => 'The name is required'
        )));
        $name->addValidator(new StringLength(array(
            'min' => 1,
            'messageMinimum' => 'The name is too short'
        )));
        $this->add($name);
        
        $message_id = new Text("message_id");
        $message_id->addValidator(new PresenceOf(array(
            'message' => 'The message_id is required'
        )));
        $this->add($message_id);
        
        $template_id = new Text("template_id");
        $template_id->addValidator(new PresenceOf(array(
            'message' => 'The template_id is required'
        )));
        $this->add($template_id);
        

    }
}

