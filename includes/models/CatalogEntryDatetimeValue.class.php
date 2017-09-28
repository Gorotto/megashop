<?

class CatalogEntryDatetimeValue extends CatalogEntryAbstractValue {

    static function definition() {
        $definition = parent::definition();
        $definition['value'] = new NamiDatetimeDbField(array(
            'format' => '%d.%m.%Y',
            'localized' => false,
            'index' => true,
        ));
        return $definition;
    }

}
