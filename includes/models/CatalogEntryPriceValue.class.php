<?

class CatalogEntryPriceValue extends CatalogEntryAbstractValue {

    static function definition() {
        $definition = parent::definition();
        $definition['value'] = new NamiPriceDbField(array('index' => true));
        return $definition;
    }

}
