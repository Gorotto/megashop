<?php

class TextPageApplication extends UriConfApplication {

    protected $uriconf = array(
        array('~^/?$~', 'index'),
    );

    function index($vars, $page, $uri) {
        print new View('page-text', array('page' => $page));
        return true;
    }

}

