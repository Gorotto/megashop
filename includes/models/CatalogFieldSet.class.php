<?

class CatalogFieldSet extends NamiSortableModel {

    static function definition() {
        return array(
            'enabled' => new NamiBoolDbField(array('default' => true, 'index' => true)),
            'title' => new NamiCharDbField(array('maxlength' => 255)),
        );
    }

    public $description = array(
        'title' => array('title' => 'Название набора полей'),
    );

}
