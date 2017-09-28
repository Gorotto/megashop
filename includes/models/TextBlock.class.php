<?

/**
  Текстовый блок сайта
 */
class TextBlock extends NamiModel {

    static private $preview_length = 100;

    static function definition() {
        return array(
            'name' => new NamiCharDbField(array('maxlength' => 255, 'null' => false, 'index' => true)),
            'text' => new NamiTextDbField(),
            'preview' => new NamiCharDbField(array('maxlength' => self::$preview_length)),
            'enabled' => new NamiBoolDbField(array('default' => 1, 'index' => true)),
            'rich' => new NamiBoolDbField(array('default' => true, 'index' => true)),
        );
    }

    function beforeSave() {
        if (TextBlocks(array('name' => $this->name, 'id__ne' => $this->id))->count()) {
            throw new NamiValidationException("Name '{$this->name}' is already in use", $this->meta->getField('name'));
        }
        $this->preview = Meta::getTextPreview(strip_tags($this->text), self::$preview_length);
    }

    function __toString() {
        return (string) ( $this->enabled ? $this->text : null );
    }

}
