<?

class CatalogEntryStringValue extends CatalogEntryAbstractValue {

    static function definition() {
        $definition = parent::definition();
        $definition['value'] = new NamiCharDbField(array('maxlength' => 500, 'index' => true));
        return $definition;
    }

}
