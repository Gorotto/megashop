<?

class CatalogEntryFieldValue extends NamiModel {

    static function definition() {
        return array(
            'entry' => new NamiFkDbField(array('model' => 'CatalogEntry', 'related' => 'fields')),
            'field' => new NamiFkDbField(array('model' => 'CatalogField', 'related' => 'entries')),
            'text_value' => new NamiTextDbField(array('localized' => false)),
            'int_value' => new NamiIntegerDbField(),
        );
    }

}
