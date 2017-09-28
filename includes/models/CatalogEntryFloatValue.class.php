<?

class CatalogEntryFloatValue extends CatalogEntryAbstractValue {

    static function definition() {
        $definition = parent::definition();
        $definition['value'] = new NamiFloatDbField(array('index' => true));
        return $definition;
    }

}
