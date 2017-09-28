<?php

/**
 * Страница сайта
 */
class Page extends NamiNestedSetModel {

    static function definition() {
        return [
            'title' => new NamiCharDbField(['maxlength' => 1000, 'default' => 'Новая страница']),
            'name' => new NamiCharDbField(['maxlength' => 50, 'index' => true]),
            'uri' => new NamiCharDbField(['maxlength' => 300, 'index' => true]),
            'text' => new NamiTextDbField(),
            'type' => new NamiFkDbField(['model' => 'PageType', 'null' => false, 'index' => true, 'default' => 1,]),
            'enabled' => new NamiBoolDbField(['default' => 0, 'index' => 'nav', 'localized' => true]),
            'meta_title' => new NamiCharDbField(['maxlength' => 1000]),
            'meta_keywords' => new NamiTextDbField(),
            'meta_description' => new NamiTextDbField(),
            'images' => new NamiArrayDbField([
                'localized' => true,
                'type' => 'NamiImageDbField',
                'path' => '/static/uploaded/images/pages',
                'variants' => [
                    'preview' => ['width' => 270, 'height' => 183, 'crop' => true],
                    'full' => ['width' => 1280, 'height' => 800, 'enlarge' => false],
                    'cms' => ['width' => 120, 'height' => 120, 'crop' => true],
                ]]
            ),
            'files' => new NamiArrayDbField([
                'type' => 'NamiFileDbField',
                'path' => '/static/uploaded/files/pages',
                    ]),
            //кастомный редактор
//            'text_type' => new NamiCharDbField(['maxlength' => 250]),
//            'text_simple' => new NamiTextDbField(),
//            'text_custom' => new NamiFkDbField(['model' => 'CustomPublication']),
            //конторлы для админа
            'hide_drag_interface' => new NamiBoolDbField(['index' => true, 'default' => false]),
            'hide_edit_interface' => new NamiBoolDbField(['index' => true, 'default' => false]),
        ];
    }

    public $description = [
        'title' => ['title' => 'Название страницы'],
        'name' => ['title' => 'Название на англ.'],
        'text' => ['title' => 'Текст страницы', 'widget' => 'richtext'],
        'images' => ['title' => 'Изображения', 'widget' => 'images'],
        'files' => ['title' => 'Файлы', 'widget' => 'files'],
        'meta' => ['title' => 'Ключевые слова, описание и заголовок (SEO)', 'widget' => 'seo_fields'],
    ];

    protected function beforeSave() {
        // Имя главной страницы - /
        if ($this->lvl == 1) {
            $this->name = '/';
            return true;
        }

        // Приводим в порядок name страницы
        if (!Meta::isPathName($this->name)) {
            // Попробуем сначала довести до ума исходный name
            if ($this->name) {
                $this->name = Meta::getPathName($this->name);
            }

            // Не получилось — сделаем на основе title
            if (!Meta::isPathName($this->name)) {
                $this->name = Meta::getPathName($this->title);
            }
        }
    }

    protected function afterSave($is_new) {
        //проверка на дубликаты среди соседей
        if ($this->isDirty('name', 'lvl') || $is_new) {
            if (Pages()->filterSiblings($this)->filter(['id__ne' => $this->id, 'name' => $this->name])->count() > 0) {
                $this->name .= $this->id;
                $this->hiddenSave();
            }

            // Генерируем полный uri страницы
            if ($this->lvl > 1) {
                $pages_names = Pages()
                        ->filterParents($this)
                        ->embrace($this)
                        ->filterLevel(2, 0)
                        ->treeOrder()
                        ->values("name");

                $uri = '/' . join('/', $pages_names) . "/";
            } else {
                $uri = "/";
            }

            if ($this->uri != $uri) {
                $this->uri = $uri;
                $this->hiddenSave();
            }

            //проверяем дочерние элементы
            $sub_pages = Pages()->filterChildren($this)->filterLevel($this->lvl + 1)->all();
            if ($sub_pages) {
                foreach ($sub_pages as $sub_page) {
                    $sub_page->markDirty("name");
                    $sub_page->save();
                }
            }
        }
    }

    protected function beforeDelete() {
        $menu_items = MenuItems()
                ->filter([
                    "link_page" => $this
                ])
                ->all();

        foreach ($menu_items as $menu_item) {
            $menu_item->delete();
        }
    }

}
