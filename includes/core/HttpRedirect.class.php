<?php

/**
*	Исключение, перехватываемое методом Builder->run для выполнения перенаправления.
*/
class HttpRedirect extends HttpException {
    protected $uri;
    protected $permanent;

    function __construct($uri, $permanent = true) {
        parent::__construct();
        
        $this->uri = $uri;
        $this->permanent = $permanent ? true : false;
    }
    
    /**
    *   Получение кода HTTP
    */
    function getHttpCode() {
        return $this->permanent ? 301 : 302;
    }
    
    /**
    *   Получение адреса перенаправления
    */
    function getLocation() {
        return $this->uri;
    }
    
    function getHttpHeader() {
        return "Location: {$this->uri}";
    }
}