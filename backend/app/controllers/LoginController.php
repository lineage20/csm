<?php

class LoginController extends \Phalcon\Mvc\Controller
{
    public function indexAction(){
    }
    private function _registerSession($user)
    {
        $this->session->set('auth', array(
            'id' => $user->id,
            'name' => $user->username,
            'url'=> $user->url
        ));
    }
    public function startAction(){
        if ($this->request->isPost()) {

            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');

            $user = Division::findFirst(array(
                "username = :username: AND password = :password:",
                'bind' => array('username' => $username, 'password' => md5($password))
            ));
            if ($user != false) {
                $this->_registerSession($user);
                return $this->response->redirect('/');
            }else {
            $this->response->redirect('login?msg=user or password error');
            $this->view->disable();
        }
        }

        return $this->response->redirect('index');
    }
    public function endAction()
    {
        $this->session->remove('auth');
        return $this->response->redirect('/');
    }
    
}

