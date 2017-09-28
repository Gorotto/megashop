<?

class CatalogCategory extends NamiNestedSetModel {

    static function definition() {
        return array(
            'title' => new NamiCharDbField(array('maxlength' => 200, 'default' => 'Новый раздел', 'null' => false)),
            'name' => new NamiCharDbField(array('maxlength' => 50, 'index' => true)),
            'uri' => new NamiCharDbField(array('maxlength' => 300, 'index' => 'nav')),
            'fieldset' => new NamiFkDbField(array('model' => 'CatalogFieldSet', 'related' => 'Catalog_categories', 'null' => true)),
            'enabled' => new NamiBoolDbField(array('default' => false, 'index' => true)),
        );
    }

    public $description = array(
        'title' => array('title' => 'Название'),
        'name' => array('title' => 'Название для URL'),
        'fieldset' => array('title' => 'Набор полей', 'widget' => 'select', 'choices' => 'CatalogFieldSet'),
    );

    function __full_uri() {
        return Builder::getAppUri('CatalogApplication') . $this->uri;
    }

    function beforeSave() {
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

    function afterSave($is_new) {
        //проверка на дубликаты среди соседей
        if ($this->isDirty('name', 'lvl') || $is_new) {
            if (CatalogCategories()->filterSiblings($this)->filter(array('id__ne' => $this->id, 'name' => $this->name))->count() > 0) {
                $this->name .= $this->id;
                $this->hiddenSave();
            }

            // Генерируем полный uri страницы
            if ($this->lvl > 1) {
                $pages_names = CatalogCategories()
                    ->filterParents($this)
                    ->embrace($this)
                    ->filterLevel(2, 0)
                    ->treeOrder()
                    ->values("name");

                $uri = join('/', $pages_names) . "/";
            } else {
                $uri = "";
            }

            if ($this->uri != $uri) {
                $this->uri = $uri;
                $this->hiddenSave();
            }

            //проверяем дочерние элементы
            $sub_pages = CatalogCategories()->filterChildren($this)->filterLevel($this->lvl + 1)->all();
            if ($sub_pages) {
                foreach ($sub_pages as $sub_page) {
                    $sub_page->markDirty("name");
                    $sub_page->save();
                }
            }
        }
    }

    function beforeDelete() {
        $this_entries = CatalogEntries(array("category" => $this->id))->first();
        if ($this_entries) {
            throw new Exception("Невозможно удалить раздел «{$this->title}», в нем есть записи.");
        }
    }

}
