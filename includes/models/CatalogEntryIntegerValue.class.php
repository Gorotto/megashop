<?

class CatalogEntryIntegerValue extends CatalogEntryAbstractValue {

    static function definition() {
        $definition = parent::definition();
        $definition['value'] = new NamiIntegerDbField(array('index' => true));
        return $definition;
    }

}
