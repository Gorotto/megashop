<?

/**
 * Module class.
 * Модуль административного интерфейса
 * @extends NamiModel
 */
class Module extends NamiSortableModel {

    private $instance = null;  // Инициализированный объект модуля, соответствующего classname

    static function definition() {
        return array(
            'title' => new NamiCharDbField(array('maxlength' => 100, 'default' => 'Новый модуль')),
            'name' => new NamiCharDbField(array('maxlength' => 100, 'default' => 'new-module', 'index' => true)),
            'classname' => new NamiCharDbField(array('maxlength' => 100, 'default' => 'AbstractModule')),
            'icon_name' => new NamiCharDbField(array('maxlength' => 100)),
            'enabled' => new NamiBoolDbField(array('default' => true)),
            'system' => new NamiBoolDbField(array('default' => false)),
        );
    }

    public $description = array(
        'title' => array('title' => 'Название'),
        'name' => array('title' => 'uri'),
        'icon_name' => array('title' => 'Иконка'),
        'classname' => array('title' => 'Контроллер'),
    );

    function beforeSave() {
        // Если в базе уже есть объект с таким name
        if (Modules()->filter(array('id__ne' => $this->id, 'name' => $this->name))->first()) {
            throw new Exception("Модуль с именем {$this->name} уже существует. Придумайте другое имя.");
        }

        // Если в базе уже есть объект с таким classname
        if (Modules()->filter(array('id__ne' => $this->id, 'classname' => $this->classname))->first()) {
            throw new Exception("Класс с именем {$this->classname} уже существует. Придумайте другое имя.");
        }
    }

    private function getInstance() {
        if (!$this->instance) {
            $this->instance = new $this->classname($this->name);
        }

        return $this->instance;
    }

    /**
      Получение контента модуля
     */
    function handleHtmlRequest($uri) {
        return $this->getInstance()->handleHtmlRequest($uri);
    }

    /**
      Получение ajax контента модуля
     */
    function handleAjaxRequest($uri) {
        return $this->getInstance()->handleAjaxRequest($uri);
    }

    /**
      Получение название модуля
     */
    function getTitle() {
        return $this->getInstance()->getTitle();
    }

    function getHint() {
        return $this->getInstance()->getHint();
    }

    function getHideMenu() {
        return $this->getInstance()->getHideMenu();
    }

}
