<?

class CatalogEntryTextValue extends CatalogEntryAbstractValue {

    static function definition() {
        $definition = parent::definition();
        $definition['value'] = new NamiTextDbField(array('localized' => false));
        return $definition;
    }

}
