<?

class CatalogEntryImagesValue extends CatalogEntryAbstractValue {

    static function definition() {
        $definition = parent::definition();
        $definition['value'] = new NamiArrayDbField(array(
            'type' => 'NamiImageDbField',
            'path' => '/static/uploaded/images/Catalog',
            'variants' => array(
                'large' => array('width' => 800, 'height' => 800),
                'medium' => array('width' => 160, 'height' => 160, 'spacefill' => true),
                'small' => array('width' => 60, 'height' => 60, 'spacefill' => true),
                'cms' => array('width' => 120, 'height' => 120, 'crop' => true),
            ),
            'localized' => false
        ));
        return $definition;
    }

}
