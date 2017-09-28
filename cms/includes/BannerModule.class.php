<?

class BannerModule extends AbstractModule {

    protected $uriconf = array(
        array('~^/?$~', 'banners'),
        array('~^/places/?(?:/page\d+)?/?$~', 'banners_places'),
    );

    function banners($vars, $uri) {
        $items = Banners()->order("title");
        return $this->getView('banners', array(
                'paginator' => new NamiPaginator($items, 'core/paginator', 20)
        ));
    }

    function banners_places($vars, $uri) {
        if (!CmsApplication::is_develop_mode()) {
            Builder::show404();
        }

        $items = BannerPlaces()->order("title");
        return $this->getView('places', array(
                'paginator' => new NamiPaginator($items, 'core/paginator', 40)
        ));
    }

}
