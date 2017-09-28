<?

/**
  Тип страницы сайта
 */
class PageType extends NamiModel {

    private $instance = null;  // Инициализированный объект класса app_classname

    static function definition() {
        return array(
            'title' => new NamiCharDbField(array('localized' => false, 'maxlength' => 100)),
            'app_classname' => new NamiCharDbField(array('localized' => false, 'maxlength' => 100, 'default' => 'AbstractApplication', 'index' => true)),
            'enabled' => new NamiBoolDbField(array('default' => true)),
            'has_text' => new NamiBoolDbField(array('default' => true)),
            'has_meta' => new NamiBoolDbField(array('default' => true)),
            'has_subpages' => new NamiBoolDbField(array('default' => false)),
        );
    }

    public $description = array(
        'title' => array('title' => 'Название'),
        'app_classname' => array('title' => 'Контроллер'),
        'has_text' => array('title' => 'Наличие текстового редактора'),
        'has_meta' => array('title' => 'Наличие мета тегов'),
        'has_subpages' => array('title' => 'Наличие подстраниц'),
    );

    /**
      Получение объекта приложения
     */
    public function getApplicationInstance() {
        if (!$this->instance) {
            $this->instance = new $this->app_classname();
        }

        return $this->instance;
    }

}
