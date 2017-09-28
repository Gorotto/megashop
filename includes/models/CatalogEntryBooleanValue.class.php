<?

class CatalogEntryBooleanValue extends CatalogEntryAbstractValue {

    static function definition() {
        $definition = parent::definition();
        $definition['value'] = new NamiBoolDbField(array('index' => true));
        return $definition;
    }

}
