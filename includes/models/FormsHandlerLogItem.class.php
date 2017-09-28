<?

//лог форм
class FormsHandlerLogItem extends NamiModel {

    static function definition() {
        return array(
            'type' => new NamiCharDbField(array('maxlength' => 250)),
            'title' => new NamiCharDbField(array('maxlength' => 250)),
            'text' => new NamiTextDbField(),
            'user_info' => new NamiTextDbField(),
            'is_new' => new NamiBoolDbField(array('default' => true, 'index' => true)),
            'date' => new NamiDatetimeDbField(array('default_callback' => 'return time();', 'format' => '%d.%m.%Y %H:%M:%S', 'index' => true)),
        );
    }

    function afterSave($is_new) {
        if (!$is_new) {
            $this->is_new = false;
            $this->hiddenSave();
        }


        $items_to_kill = FormsHandlerLogItems()
            ->orderDesc("date")
            ->limit(20, 1000);

        if ($items_to_kill) {
            foreach ($items_to_kill as $img) {
                $img->delete();
            }
        }
    }

    function beforeSave() {
        $this->title = Meta::cut_text($this->text, 60);
    }

}
