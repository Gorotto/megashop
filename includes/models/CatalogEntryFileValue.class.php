<?

class CatalogEntryFileValue extends CatalogEntryAbstractValue {

    static function definition() {
        $definition = parent::definition();
        $definition['value'] = new NamiFileDbField(array('path' => '/static/uploaded/files/Catalog'));
        return $definition;
    }

}
