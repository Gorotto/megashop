<?

class CatalogFieldType extends NamiSortableModel {

    static function definition() {
        return array(
            'title' => new NamiCharDbField(array('maxlength' => 255, 'localized' => false)),
            // Виджет, используемый в админке для редактирования поля этого типа
            'editor_widget' => new NamiCharDbField(array('maxlength' => 50, 'localized' => false)),
            // Класс значения поля, должен быть потомком CatalogEntryAbstractValue, или чем-то похожим
            'storage_model' => new NamiCharDbField(array('maxlength' => 50, 'localized' => false)),
            'enabled' => new NamiBoolDbField(array('default' => false, 'index' => true)),
        );
    }

}

/*
 * Стаднартная поставка полей
 
INSERT INTO `catalogfieldtype` (`id`, `title`, `editor_widget`, `storage_model`, `sortpos`, `enabled`) VALUES
(1, 'Строка', 'string', 'CatalogEntryStringValue', 1, 1),
(2, 'Целое число', 'integer', 'CatalogEntryIntegerValue', 2, 1),
(3, 'Флажок', 'checkbox', 'CatalogEntryBooleanValue', 5, 1),
(4, 'Строка из списка', 'combobox', 'CatalogEntryStringValue', 4, 1),
(5, 'Число с десятичной точкой', 'float', 'CatalogEntryFloatValue', 3, 1);
 */
