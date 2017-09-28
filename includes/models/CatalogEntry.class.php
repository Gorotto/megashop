<?php

class CatalogEntry extends CatalogEntryModel {

    static function definition() {
        return array(
            'category' => new NamiFkDbField(array('model' => 'CatalogCategory', 'null' => false)),
            'title' => new NamiCharDbField(array('maxlength' => 500)),
            //filter поле
            'has_images' => new NamiBoolDbField(array('default' => false, 'index' => true)),
            'images' => new NamiArrayDbField(array(
                'type' => 'NamiImageDbField',
                'path' => '/static/uploaded/images/catalog',
                'variants' => array(
                    'cms' => array('width' => 120, 'height' => 120, 'crop' => true),
                ))),
            'text' => new NamiTextDbField(array('default' => '')),
            'price' => new NamiPriceDbField(),
            'resaved' => new NamiBoolDbField(array('default' => false, 'index' => true)),
            'enabled' => new NamiBoolDbField(array('default' => false, 'index' => true)),
            'edited' => new NamiBoolDbField(array('default' => false, 'index' => true)),
//            'updated_at' => new NamiDatetimeDbField(array('default_callback' => 'return time();', 'format' => '%d.%m.%Y %H:%M:%S', 'index' => true)),
        );
    }

    public $description = array(
        'title' => array('title' => 'Название'),
        'images' => array('title' => 'Изображения. Рекомендуемый размер 100x100 пикс.', 'widget' => 'images'),
        'text' => array('title' => 'Описание', 'widget' => 'richtext'),
        'price' => array('title' => 'Цена'),
        'edited' => array('title' => 'Информация о товаре заполнена'),
    );

    function __full_uri() {
        return $this->category->full_uri . "item-" . $this->id . "/";
    }

    function __price_formated() {
        return Meta::beauty_price($this->price);
    }

    function beforeSave() {
        //если цены нет - принудительно запишем ноль
        //это упростит orm запросы фильтрации
        if (!$this->price) {
            $this->price = "0";
        }

        if ($this->images->length) {
            $this->has_images = true;
        } else {
            $this->has_images = false;
        }

//        $this->updated_at = time();
    }

    function afterSave() {
//        сохранение кастомных полей в один список
//
//        $imp_fields = $this->getExtraFields(true);
//        $text = array();
//
//        foreach ($imp_fields as $name => $field) {
//            if ($field->value) {
//                $text_ = $field->field->title;
//
//                if (get_class($field) == "CatalogEntryBooleanValue") {
//                    $text_ .= " — " . ($field->value ? "Да" : "Нет");
//                } else {
//                    $text_ .= " — " . $field->value;
//                }
//
//                if ($field->field->unit) {
//                    $text_ .= " " . $field->field->unit;
//                }
//
//                $text[] = $text_;
//            }
//        }
//
//        $this->imp_fields_text = "<li>" . implode("</li><li>", $text) . "</li>";
//        $this->hiddenSave();
    }

}
