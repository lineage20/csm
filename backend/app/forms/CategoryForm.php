<?php

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;


class CategoryForm extends Form
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

    }
}

