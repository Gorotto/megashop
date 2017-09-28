<?php

class BuilderSettingsModule extends AbstractModule {

    protected $uriconf = array(
        array('~^/?(?:/page\d+)?/?$~', 'items'),
    );

    function items($vars, $uri) {
        if (CmsApplication::is_develop_mode()) {
            $items = BuilderSettings()->orderDesc("title");
        } else {
            $items = BuilderSettings(array('visible' => true))->orderDesc("title");
        }

        $paginator = new NamiPaginator($items, 'core/paginator', 40);

        return $this->getView('items', array(
                'paginator' => $paginator
        ));
    }

}