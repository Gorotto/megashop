<?php
/**
*   Абстрактный предок для значений каталога разных типов.
*   Нужно его унаследовать, переопределить метод definition, в котором вызвать родительский
*   и дописать в результат его работы поле value требуемого типа.
*/
abstract class CatalogEntryAbstractValue extends NamiModel {
    static function definition() {
        // Основываясь на имени модели сгенерим related_name для Entry
        $related_name = 'values';
        if (preg_match('/^CatalogEntry(\w+)Value$/u', get_class(), $matches)) {
            $related_name = mb_strtolower($matches[1]) . '_values';
        }

        return array(
            'entry' => new NamiFkDbField(array('model' => 'CatalogEntry', 'related' => $related_name)),
            'field' => new NamiFkDbField(array('model' => 'CatalogField', 'related' => 'values')),
        );
    }

    /**
    *   Получение объекта - поля, представляющего хранимое значение.
    *   Обычно модель хранит это в себе и наружу не показывает, но нам это потребуется в CatalogEntryModel,
    *   потому что она будет использовать дополнительные поля как свои.
    */
    function __value_object() {
        return $this->meta->getField('value');
    }
}