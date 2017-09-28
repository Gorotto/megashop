<?

class BuilderSetting extends NamiModel {

    static function definition() {
        return array(
            'title' => new NamiCharDbField(array('maxlength' => 200)),
            'name' => new NamiCharDbField(array('maxlength' => 50, 'null' => false, 'index' => true)),
            'value' => new NamiCharDbField(array('maxlength' => 500)),
            'description' => new NamiTextDbField(array('localized' => false)),
            'visible' => new NamiBoolDbField(array('default' => false)),
        );
    }

}
