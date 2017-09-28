<?php

abstract class MenuItem extends NamiNestedSetModel {
    
    static $positions = [
        'top' => 'верхнее, первое',
        'bottom' => 'нижнее, подвал',
    ];

    static function definition() {
        return [
            'title' => new NamiCharDbField(['maxlength' => 250]),
            'type' => new NamiCharDbField(['maxlength' => 250]),
            'link_page' => new NamiFkDbField(['model' => 'Page']),
            'link_text' => new NamiCharDbField(['maxlength' => 250]),
            'enabled' => new NamiBoolDbField(['default' => false, 'index' => true]),
        ];
    }


    public $description = [
        'type' => [
            'title' => 'Тип ссылки',
            'widget' => 'form_control',
            'values' => [
                'page' => [
                    'title' => 'Страница сайта',
                    'hide' => ['link_text', 'title'],
                    'show' => ['link_page']
                ],
                'text' => [
                    'title' => 'Сторонняя ссылка',
                    'hide' => ['link_page'],
                    'show' => ['link_text', 'title']
                ],
            ]
        ],
        'link_page' => ['title' => 'Страница', 'widget' => 'select', 'choices' => 'Page'],
        'title' => ['title' => 'Название'],
        'link_text' => ['title' => 'Ссылка'],
    ];


    function beforeSave()
    {
        if ($this->type == "page" && !$this->link_page) {
            throw new Exception("Не указана страница");
        }

        if ($this->type == "page") {
            $this->title = $this->link_page->title;
        }
    }

    function __uri(){
        if ($this->type == "page") {
            return $this->link_page->uri;
        }else{
            return $this->link_text;
        }
    }
}
