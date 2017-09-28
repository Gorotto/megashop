<?php

/**
  Прародитель модулей административного интерфейса
 */
class AbstractModule {

    protected $uri;
    protected $cmsUri;
    protected $ajaxUri;
    protected $uriPath;
    protected $hint = '';
    protected $hideMenu = false;

    /**
     *   Конструктор
     *   $name - имя, под которым модуль будет работать
     */
    function __construct($name) {

        // Проверим наличие имени
        if (!$name) {
            throw new Exception("Module cannot be constructed without a name");
        }

        // Получим всякие uri от запроса CMS
        $request = new CmsRequest();

        $this->cmsUri = $request->cmsUri;
        $this->uri = "{$this->cmsUri}/{$name}";
        $this->ajaxUri = "{$this->cmsUri}/ajax/{$name}";
        $this->uriPath = $this->cmsUri . $request->objectName == $name ? "/{$name}" . ( $request->uri != '/' ? $request->uri : '' ) : "/{$name}";
    }

    /**
     *   Получение шаблона модуля с указанным именем.
     *   Равноценно вызову new View с теми же аргументами за исключением того, что путь к шаблону
     *   будет автоматически модифицирован, а в массив переменых будет добавлен элемент module
     *   Вызов $this->getView( 'index' ) изнутри MahModule равносилен
     *   new View( 'modules/MahModule/index', array( module = $this ) )
     *   Возвращает объект View
     */
    function getView($file, $vars = array()) {

        foreach (get_object_vars($this) as $name => $value) {
            $vars[$name] = $value;
        }

        return new View("modules/" . get_class($this) . "/{$file}", $vars);
    }

    /**
     *   Получение ответа на HTML-запрос.
     *   Может и чаще всего должен быть переопределен в потомках для реализации непростой функциональности.
     */
    function handleHtmlRequest($uri) {
        // Если у класса имеется свойство $uriconf - сделаем авто-ресолв, иначе - грузим view 'index'
        if (property_exists($this, 'uriconf')) {
            $uriconf = array_merge($this->uriconf, array(array('~.*~', 'index')));
            $result = $this->resolveUri(new UriConf($uriconf), $uri);
            if ($result !== false) {
                return $result;
            }
        }

        return $this->getView('index');
    }

    function index($vars, $uri) {
        return $this->getView('index');
    }

    /**
     *   Получение ответа на HTML-запрос.
     *   Может быть переопределен в потомках для реализации непростой функциональности, недоступной через запросы к моделям.
     */
    function handleAjaxRequest($uri) {
        return json_encode(array(success => true, message => get_class($this) . ' приветствует Вас и желает Вам приятного дня.'));
    }

    public function getHint() {
        return $this->hint;
    }

    public function getHideMenu() {
        return $this->hideMenu;
    }

    /**
     *   Автоматический ресолв UriConf'а и запуск соответствующего метода.
     *   $conf - экземпляр UriConf
     *   $uri  - адрес, которые требуется разресолвить
     *   Выполняет метод с именем $uriconf_result, если ресолв прошел.
     *   Выполняемому методу передается три аргумента - $uriconf->vars (объект матчей из адреса uriconf),
     *   $action (имя метода) и $uri.
     *   Возвращает false, если ничего не нашлось или результат работы метода.
     *   Если uriconf вернул метод, но он не найден в текущем объекте - выбрасывает исключение.
     */
    protected function resolveUri($uriconf, $uri) {
        $action = $uriconf->resolve($uri);

        if ($action) {
            if (method_exists($this, $action)) {
                return $this->$action($uriconf->vars, $action, $uri);
            } else {
                throw new Exception(sprintf("Action %s::%s not found", get_class($this), $action));
            }
        }

        return false;
    }

}
