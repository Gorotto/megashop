<?php

class UserModule extends AbstractModule {

    protected $uriconf = array(
        array('~^/?$~', 'index'),
    );

    function index($vars, $uri) {
        $freez_logins = ["admin"];

        $items = Users()
            ->filter(array(
                "login__notin" => $freez_logins
            ))
            ->orderDesc('login');

        return $this->getView('items', array(
                'paginator' => new NamiPaginator($items, 'core/paginator', 20),
        ));
    }

}
