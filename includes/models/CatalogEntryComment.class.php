<?

class CatalogEntryComment extends NamiModel {

    static private $preview_length = 100;

    static function definition() {
        return array(
            'entry' => new NamiFkDbField(array('model' => 'CatalogEntry', 'related' => 'comments')),
            'enabled' => new NamiBoolDbField(array('default' => true, 'index' => true)),
            'datetime' => new NamiDatetimeDbField(array('default_callback' => 'return time();', 'format' => '%d.%m.%Y %H:%M', 'index' => true, 'null' => false)),
            'name' => new NamiCharDbField(array('maxlength' => 500,)),
            'text' => new NamiTextDbField(array('localized' => false)),
            'preview' => new NamiCharDbField(array('maxlength' => self::$preview_length)),
        );
    }

    public $description = array(
        'datetime' => array('title' => 'Дата'),
        'name' => array('title' => 'Автор'),
        'text' => array('title' => 'Комментарий'),
    );

    function beforeSave() {
        if (TextBlocks(array('name' => $this->name, 'id__ne' => $this->id))->count()) {
            throw new NamiValidationException("Name '{$this->name}' is already in use", $this->meta->getField('name'));
        }
        $this->preview = Meta::getTextPreview(strip_tags($this->text), self::$preview_length);
    }

}
