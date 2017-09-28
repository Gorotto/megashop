<?

class CatalogFieldSetField extends NamiSortableModel {

    static function definition() {
        return array(
            'title' => new NamiCharDbField(array('maxlength' => 250)),
            'fieldset' => new NamiFkDbField(array('model' => 'CatalogFieldSet', 'related' => 'fields', 'null' => false)),
            'field' => new NamiFkDbField(array('model' => 'CatalogField', 'related' => 'fieldsets', 'null' => false)),
            'filter_mode' => new NamiCharDbField(array('maxlength' => 255)),
        );
    }

    public $description = array(
        'filter_mode' => array('title' => 'Тип фильтра на сайте', 'widget' => 'select'),
    );
    public static $filter_modes = array(
        "" => "Фильтр по этому полю отключен",
        "range" => "От и до",
        "select" => "Выпадающий список",
        "checkbox" => "Чекбокс",
    );

    function construct() {
        $this->description['filter_mode']['choices'] = self::$filter_modes;
    }

    function beforeSave() {
        $field = CatalogFieldSetFields()->filter(array('fieldset' => $this->fieldset, 'field' => $this->field))->first();
        if ($field && $field->id != $this->id) {
            throw new Exception("Поле в наборе полей повторяться не может");
        }

        $this->title = $this->field->title_cms . " (" . $this->field->field_type->title . ")";
    }

}
