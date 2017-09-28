<?php

class SiteUsersModule extends AbstractModule {

    protected $uriconf = array(
        array('~^(?:/page\d+)?/?$~', 'index'),
    );

    function index($vars, $uri) {
        $user_filter_params = array();

        if (Meta::vars('filter_mail')) {
            $user_filter_params['email__icontains'] = trim(Meta::vars('filter_mail'));
        }

        $items = SiteUsers()->filter($user_filter_params)->order("email");

        return $this->getView('items', array(
                'paginator' => new NamiPaginator($items, 'core/paginator', 20),
        ));
    }

}
