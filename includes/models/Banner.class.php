<?php

class Banner extends NamiSortableModel {

    static function definition() {
        return array(
            'title' => new NamiCharDbField(array('maxlength' => 255, 'default' => '',)),
            'src' => new NamiFileDbField(array('path' => "/static/uploaded/banners",)),
            'uri' => new NamiCharDbField(array('maxlength' => 500)),
            'start' => new NamiDatetimeDbField(array('default_callback' => 'return time();', 'format' => '%d.%m.%Y %H:%M:%S', 'index' => 'nav',)),
            'stop' => new NamiDatetimeDbField(array('default_callback' => 'return time() + 604800;', 'format' => '%d.%m.%Y %H:%M:%S', 'index' => 'nav',)),
            'views' => new NamiIntegerDbField(array('default' => 0,)),
            'clicks' => new NamiIntegerDbField(array('default' => 0,)),
            'enabled' => new NamiBoolDbField(array('default' => true, 'index' => 'nav',)),
            'scheduler' => new NamiBoolDbField(array('default' => false, 'index' => true)),
            'places_ids' => new NamiCharDbField(array('maxlength' => 250)),
        );
    }

    public $description = array(
        'title' => array('title' => 'Название'),
        'src' => array('title' => 'Файл баннера', 'widget' => 'file'),
        'uri' => array('title' => 'Ссылка'),
        'scheduler' => array('title' => 'Показывать баннер по расписанию'),
        'start' => array('title' => 'Время начала показа', 'widget' => 'datetime', 'depend_of' => 'scheduler'),
        'stop' => array('title' => 'Время окончания показа', 'widget' => 'datetime', 'depend_of' => 'scheduler'),
//        'places_ids' => array('title' => 'Отображать на баннерных местах', 'widget' => 'chosen', 'choices' => 'BannerPlace'),
    );

    function afterSave() {
        // ссылки для линк модели
        $main_model_data = array(
            "id" => $this->id,
            "related_ids_new_value" => $this->places_ids,
        );

        $link_model_data = array(
            "name" => "BannerPlaceBanner",
            "main_model_field_name" => "banner",
            "related_model_field_name" => "bannerplace",
        );

        LinksProcessor::check_links($main_model_data, $link_model_data);
    }

    function beforeDelete() {
        $remove_params = array(
            "links_model_name" => "BannerPlaceBanner",
            "link_field_name" => "banner",
            "link_field_value" => $this->id,
        );

        LinksProcessor::remove_links($remove_params);
    }

    function __type() {
        if ($this->src) {
            switch (mb_strtolower(pathinfo($this->src->name, PATHINFO_EXTENSION))) {
                case 'jpg':
                case 'jpeg':
                case 'gif':
                case 'png':
                    return 'image';
                    break;
                case 'swf':
                    return 'flash';
                    break;
            }
        }
        return 'empty';
    }

    function __link() {
        return "/go/?id={$this->id}";
    }

    static function get_counter() {
        static $counter = 1;
        return $counter++;
    }

}
