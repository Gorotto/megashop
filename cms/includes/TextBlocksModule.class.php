<?php

class TextBlocksModule extends AbstractModule {

    protected $uriconf = array(
        array('~^/?(?:/page\d+)?/?$~', 'items'),
    );

    function items($vars, $uri) {
        $items = TextBlocks()->order("name");
        $paginator = new NamiPaginator($items, 'core/paginator', 20);

        return $this->getView('items', array(
                'paginator' => $paginator
        ));
    }

}