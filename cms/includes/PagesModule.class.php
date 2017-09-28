<?php

class PagesModule extends AbstractModule {
    
    protected $uriconf = [
        ['~^/?$~', 'pages'],
        ['~^/page_types/?$~', 'page_types'],
        ['~^/menu/?$~', 'menu'],
    ];

    function pages($vars, $uri) {
        $items = Pages()->treeOrder()->follow(2)->tree();
        $types = PageTypes(['enabled' => true])->order('title')->values(array("id", "has_text", "has_meta", "title"));

        $page_types = array();
        foreach ($types as $type) {
            $page_types[$type['id']] = $type;
        }

        return $this->getView('pages', compact("page_types", "items"));
    }

    function menu($vars, $uri) {

        if (!Meta::vars("position")) {
            exit("404");
        }
        
        $menuname = "MenuItem" . lcfirst(Meta::vars("position"));

        $items = NamiQuerySet($menuname)->treeOrder()->tree();
        return $this->getView('menu', compact("items", 'menuname'));
    }

    function page_types($vars, $uri) {
        if (CmsApplication::is_develop_mode()) {
            $items = PageTypes()->all();

            return $this->getView('types', compact("items"));
        }
    }

}
