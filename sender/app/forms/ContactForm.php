<?php

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\Email;


class ContactForm extends Form
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
        
//        $email = new Email("email");
//        $email->addValidator(new PresenceOf(array(
//            'message' => 'The email is required'
//        )));
//        $email->addValidator(new Email(array(
//            'message' => 'The email is not valid'
//        )));
//        $this->add($email);

    }
}

