<?php

class ModulesModule extends AbstractModule {

    protected $uriconf = array(
        array('~^/?$~', 'modules'),
        array('~^/icons/?$~', 'icons'),
    );

    function modules($vars, $uri) {
        if (!CmsApplication::is_develop_mode()) {
            throw new Http404;
        }

        $items = Modules()
                ->sortedOrder()
                ->all();

        return $this->getView('index', compact('items'));
    }

    function icons($vars, $uri) {
        return $this->getView('icons');
    }

}
