<?php

class IndexPageApplication extends UriConfApplication {

    protected $uriconf = array(
        array('~^/?$~', 'index'),
    );

    function index($vars, $page, $uri) {
                
        
        print new View('home/page-index', compact('page'));
        return true;
    }

}
