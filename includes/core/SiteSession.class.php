<?php

class SiteSessionException extends Exception {

    protected $name;

    function __construct($name, $message) {
        $this->name = $name;
        $this->message = $message;
    }

    function getErrMessage() {
        return $this->message;
    }

}

class SiteSession {

    private $session = null; // Ссылка на PHP-сессию
    private $user = null;    // текущий пользователь, объект User
    private static $instance = null; // Единственный экземпляр класса, который позволительно создать, фишки для singletone

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new SiteSession();
        }
        return self::$instance;
    }

    protected function __construct() {
        session_start();

        // Проверим наличие в сессии наших данных, создадим, если нету
        if (!isset($_SESSION['buildersite'])) {
            $_SESSION['buildersite'] = array();
        }

        // Сохраняем ссылку на элемент глобального массива, 
        // чтобы работать уже со своим полем, и при этом модифицировать сессию PHP
        $this->session = & $_SESSION['buildersite'];

        // Проверяем сессию
        $this->checkSession();
    }

    private function checkSession() {
        // Читаем пользователя, если он есть
        $this->user = null;

        //сериализация 
        if (isset($this->session['user'])) {
            if ($this->session['user']) {
                $this->user = unserialize($this->session['user']);
            }
        }

        if ($this->user && !( @$this->session['permanent'] || @$_COOKIE['builder_site_session'] )) {
            $this->logout();
        }
    }

    public function login($email, $md5_hashed_password, $permanent) {
        // Сбрасываем текущую сессию
        $this->session['user'] = $this->user = null;

        // Выберем пользователя по email
        $user = SiteUsers(array('email' => $email))->first();

        if (!$user) {
            throw new SiteSessionException('notfound', "Введен неправильный логин или пароль.");
        }

        if ($user->password !== $md5_hashed_password) {
            throw new SiteSessionException('wrongpassword', "Введен неправильный логин или пароль.");
        }

        if (!$user->is_activated) {
            throw new SiteSessionException('notfound', "Ваша учетная не активирована.");
        }

        $user->last_login = time();
        $user->hiddenSave();

        $this->user = $user;
        $this->session['user'] = serialize($user);

        if ($permanent) {
            $this->session['permanent'] = true;
        } else {
            $this->session['permanent'] = false;
            setcookie('builder_site_session', 1, 0, '/');
        }

        return true;
    }

    /**
      На входе получаем данные формы, на выходе авторизованного пользователя,
      либо список ошибок.
     */
    public function auth($email, $pass, $remember = false) {
        $success = false;
        $error_msg = array();

        try {
            // авторизуем
            $this->login($email, md5($pass), $remember);
            $success = true;
        } catch (SiteSessionException $e) {
            $error_msg = $e->getErrMessage();
        }

        return array('success' => $success, 'error' => $error_msg);
    }

    public function logout() {
        $this->session['user'] = $this->user = null;
        return true;
    }

    public function getUser() {
        return $this->user;
    }

    public function isAuthorized() {
        return (boolean) $this->user;
    }

    public function reloadData() {
        $this->user = SiteUsers(array('id' => $this->user->id))->first();
        $this->session['user'] = $this->user ? serialize($this->user) : null;
    }

    public function __set($name, $value) {
        $this->session[$name] = $value;
    }

    public function __get($name) {
        return array_key_exists($name, $this->session) ? $this->session[$name] : null;
    }

}
