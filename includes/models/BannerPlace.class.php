<?php

class BannerPlace extends NamiModel {

    static function definition() {
        return array(
            'title' => new NamiCharDbField(array('maxlength' => 255, 'default' => '')),
            'name' => new NamiCharDbField(array('maxlength' => 50, 'index' => 'nav', 'null' => false)),
            'width' => new NamiCharDbField(array('maxlength' => 250)),
            'heigth' => new NamiCharDbField(array('maxlength' => 250)),
            'enabled' => new NamiBoolDbField(array('default' => true, 'index' => 'nav')),
        );
    }

    public $description = array(
        'title' => array('title' => 'Название'),
        'name' => array('title' => 'Название на англ.'),
        'width' => array('title' => 'Ширина'),
        'heigth' => array('title' => 'Высота'),
    );

    function beforeDelete() {
        $remove_params = array(
            "links_model_name" => "BannerPlaceBanner",
            "link_field_name" => "bannerplace",
            "link_field_value" => $this->id,
        );

        LinksProcessor::remove_links($remove_params);
    }

    static function getBanner($banner_place_name, $class = "banner") {
        $banner_link = BannerPlaceBanners()
                ->filter(array(
                    "banner__enabled" => true,
                    "bannerplace__enabled" => true,
                    "bannerplace__name" => $banner_place_name,
                    "banner__scheduler" => false
                ))
                ->embrace(array(
                    "banner__enabled" => true,
                    "bannerplace__enabled" => true,
                    "bannerplace__name" => $banner_place_name,
                    "banner__scheduler" => true,
                    "banner__start__le" => time(),
                    "banner__stop__ge" => time(),
                ))
                ->orderRand()
                ->follow(1)
                ->first();


        if ($banner_link) {
            self::$viewed_banners[$banner_link->banner->id] = true;

            $view = new View('banners/banner', array(
                'banner' => $banner_link->banner,
                'place' => $banner_link->bannerplace,
                'class' => $class
            ));
            return $view->fetch();
        }

        return null;
    }

    static function init($classname = "") {
        register_shutdown_function('BannerPlace::save_impressions');
    }

    static $viewed_banners = array();

    static function save_impressions() {
        if (self::$viewed_banners) {
            $query = sprintf("update banner set views = views + 1 where id in (%s)", join(',', array_keys(self::$viewed_banners)));
            NamiCore::getBackend()->cursor->execute($query);
        }
    }

}
