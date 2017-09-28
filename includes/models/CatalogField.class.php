<?

class CatalogField extends NamiSortableModel {

    static function definition() {
        return array(
            'field_type' => new NamiFkDbField(array('model' => 'CatalogFieldType', 'related' => 'fields', 'null' => false)),
            // title, который используется на сайте
            'title' => new NamiCharDbField(array('maxlength' => 255)),
            // title, который используется в админке
            'title_cms' => new NamiCharDbField(array('maxlength' => 255)),
            'name' => new NamiCharDbField(array('maxlength' => 255, 'index' => true)),
            'unit' => new NamiCharDbField(array('maxlength' => 255)),
            'settings' => new NamiCharDbField(array('maxlength' => 255)),
            'default' => new NamiCharDbField(array('maxlength' => 255)),
            'enabled' => new NamiBoolDbField(array('default' => true, 'index' => true)),
        );
    }

    public $description = array(
        'field_type' => array('title' => 'Тип поля', 'widget' => 'select', 'choices' => 'CatalogFieldType'),
        'title' => array('title' => 'Название для сайта'),
        'unit' => array('title' => 'Единица измерения'),
        'title_cms' => array('title' => 'Название для админки'),
        'name' => array('title' => 'Название для URL'),
        'settings' => array('title' => 'Настройки', 'widget' => 'text'),
    );

    function beforeSave() {
        if (!Meta::isPathName($this->name)) {
            if ($this->name) {
                $this->name = Meta::getPathName($this->name);
            }

            if (!Meta::isPathName($this->name)) {
                $this->name = Meta::getPathName($this->title);
            }
        }

        if (!$this->title_cms && $this->title) {
            $this->title_cms = $this->title;
        }
    }

}
