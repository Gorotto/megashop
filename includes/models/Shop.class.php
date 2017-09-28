<?php

class Shop extends CatalogEntryModel {

    static function definition() {
        return [
            'user' => new NamiFkDbField(['model' => 'SiteUser', 'index' => true]),
            'title' => new NamiCharDbField(['maxlength' => 255]),
            'text' => new NamiTextDbField(),
            'online' => new NamiBoolDbField(['default' => false]),
        ];
    }

    public $description = [
        'title' => ['title' => 'Название магазина'],
        'text' => ['title' => 'Описание магазина', 'widget' => 'richtext'],
        'online' => ['title' => 'Статус магазина']
    ];

 
}