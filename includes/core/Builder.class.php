<?php

/**
 * 	Ядро системы управления, отображающее сайт.
 */
class Builder {

    public static $version = '3.0';
    //builder parts versions
    private static $crochet_version = '0.9';
    private static $ckeditor_version = '4.2';
    //fancybox 2 key
    private static $fancy2Key = "66fa18dd73794baa785534bdfa4a144b";
    protected static $languages = array();
    protected static $language = null;
    protected static $langRegExp = null;
    protected static $appUriCache = array();
    protected static $appCache = array();
    protected static $developmentMode = true;
    //список системных страниц, которые не будут дергаться из модели Page
    private static $systemControllers = array(
//        "file-upload" => "FileUploaderApplication",
//        "vote" => "VoteApplication",
        "forms" => "FormsHandlerApplication",
//        "search" => "SearchApplication",
//        "share-img" => "ShareImageApplication",
//        "weather" => "WeatherApplication",
    );

    /**
     * 	Инициализация класса
     */
    static function init() {
        // Настроим параметры PHP
        self::tunePhp();

        // Определим нужно ли влючать режим разработки
        self::detectDevelompentMode();

        // На инициализацию устанавливаем собственные обработчики ошибок и исключений
        self::setErrorHandlers();

        // Читаем и устанавливаем локаль
        self::setLanguage();

        // Если определен текущий язык — добавляем пре-суффикс шаблонов на его основе
        if (self::$language) {
            View::addSuffix('.' . self::$language);
        }

        // Если режим разработки — синхронизируем модели
        if (self::$developmentMode) {
            NamiCore::sync();
        }
    }

    /**
     *  Runtime-конфигурирование php
     */
    static function tunePhp() {
        // максимальное время жизни сессии до garbage colection - полгода
        ini_set('session.gc_maxlifetime', 12960000);

        // не принимать сессии из URI
        ini_set('session.use_only_cookies', 1);

        // время жизни сессионной куки - два года, чтобы наверняка
        ini_set('session.cookie_lifetime', 63072000);

        date_default_timezone_set('Asia/Krasnoyarsk');
    }

    /**
     * 	Определение режима разработки
     */
    static function detectDevelompentMode() {
        if (!is_bool(self::$developmentMode)) {
            if ($_SERVER['SERVER_ADDR'] === '127.0.0.1' || strpos($_SERVER['HTTP_HOST'], '.yourcompany.ru')) {
                self::$developmentMode = true;
            } else {
                self::$developmentMode = false;
            }
        }
    }

    static function developmentMode() {
        return self::$developmentMode;
    }

    /**
     * 	Установка обработчиков ошибок и исключений.
     */
    static function setErrorHandlers() {
        set_error_handler(array("Builder", "fatal_error_handler"), E_RECOVERABLE_ERROR | E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE);
        set_exception_handler(array("Builder", "uncaught_exception_handler"));
    }

    /**
     * 	Включение правильной локали Nami.
     */
    static function setLanguage() {
        // Наполним массив языков сайта
        self::$languages = array();
        self::$langRegExp = '';

        foreach (NamiCore::getAvailableLanguages() as $language) {
            if (!array_key_exists('default', self::$languages)) {
                self::$languages['default'] = $language;
            } else {
                self::$languages[$language->name] = $language;
                self::$langRegExp .= self::$langRegExp ? "|{$language->name}" : $language->name;
            }
        }

        // Собственно проверка наличия языка
        if (self::$langRegExp) {
            if (preg_match("~^/(" . self::$langRegExp . ")(\W|$)~su", $_SERVER['REQUEST_URI'], $m)) {
                $needle = $m[1];
                self::$language = $m[1];
                if ($m[2] === '/') {
                    $needle .= '/';
                }
                $_SERVER['REQUEST_URI'] = Meta::str_replace_once($needle, '', $_SERVER['REQUEST_URI']);
                NamiCore::setLanguage(self::$languages[$m[1]]);
            }
        } else {
            self::$language = null; // default language
            NamiCore::setLanguage(self::$languages['default']);
        }

        // Выбираем локаль PHP в соответствии с локалью шторма
        setlocale(LC_ALL, NamiCore::getLanguage()->locale);
        // Устанавливаем внутреннюю кодировку мультибайтовых функций
        mb_internal_encoding(NamiCore::getLanguage()->charset);
    }

    /**
     * 	Обработка HTTP запроса к сайту.
     * 	Основное метод, через который работают все страницы.
     * 	Не возвращает ничего, текст сайта отправляется на стандартный вывод.
     */
    static function run() {
        ob_start();

        // Тут будут условия фильтрации страниц
        $filter = null;

        // Получаем текущий URI, выделяем из него имена каталогов/файлов
        $names = Meta::getUriPathNames();

        //проверка урл на наличеие системных путей
        foreach (self::$systemControllers as $page_uri => $controller_name) {
            if (isset($names[0]) && ($names[0] == $page_uri)) {
                try {
                    $page_uri = str_replace("/" . $page_uri, "", Meta::getUriPath());
                    $page = new Page();
                    $app = new $controller_name;
                    $app->run($page, $page_uri);

                    print( self::postprocess(ob_get_clean()));
                    return;
                } catch (HttpException $e) {
                    return self::process_http_exception($e);
                }
            }
        }

        /*
          Если есть имена - пытаемся выбрать одну из внутренних страниц сайта
          Например, условия выборки для страницы /news/archive/2008 получатся такие:

          ( ( uri = /news ИЛИ uri = /news/archive ИЛИ uri = /news/archive/2008 ) И type->has_subpages = true )
          ИЛИ
          ( uri = /news/archive/2008 И type->has_subpages = true)

          Условиям могут соответствовать несколько страниц, и это очень хорошо и правильно,
          главное — верно упорядочить результат :3
         */
        $uri = '/';
        $first_only = false;
        if ($names) {
            // Циклом наращиваем путь от корня вглубь, добавляем условия на выборку страниц с таким путем
            foreach ($names as $name) {
                $uri = $uri . $name . "/";
                $q = Q(array('uri' => $uri));
                $filter = $filter ? QOR($filter, $q) : $q;
            }

            // Фильтр вложенных страниц приложения
            $filter = QAND($filter, Q(array('type__has_subpages' => true)));

            // Фильтр полного совпадения uri страниц без вложенных страниц приложения
            $filter = QOR($filter, QAND(Q(array('uri' => $uri)), Q(array('type__has_subpages' => false))));

            // Фильтр главной страницы с подстраницами
            $filter = QOR($filter, QAND(Q(array('lvl' => 1)), Q(array('type__has_subpages' => true))));
        } else {
            // Путь пуст, выбираем главную страницу
            $first_only = true;
            $filter = Q(array('lvl' => 1));
        }

        // Выбираем страницы, сортируем так: сначала самые глубоко вложеные, среди одинаково вложенных — с большим приоритетом типа
        $query = Pages()
            ->filter($filter)
            ->filter(array('enabled' => true))
            ->orderDesc('lvl')
            ->follow(1);

        $pages = $first_only ? array($query->first()) : $query->all();

        foreach ($first_only ? array($query->first()) : $query->all() as $p) {
            // в $uri как раз оказывается полный uri запрошенной страницы :D
            // Отделим uri приложение внутри страницы
            if ($p->lvl > 1) {
                $app_uri = "/" . mb_substr($uri, mb_strlen($p->uri, 'utf-8'), mb_strlen($uri, 'utf-8'), 'utf-8');
            } else {
                $app_uri = $uri;
            }


            // Запускаем приложение, соответствующее типу страницы, если оно отработало — завершаем работу
            try {
                if ($p->type->getApplicationInstance()->run($p, $app_uri)) {
                    print( self::postprocess(ob_get_clean()));
                    return;
                }
            } catch (HttpException $e) {
                return self::process_http_exception($e);
            }
            // продолжаем обработку среди всех выбранных приложений, попадающих в этот же uri
        }

        ob_end_clean();
        // Не сработало ни одно приложение — ничего не найдено

        if ($names[0] == 403) {
            Builder::show403();
        }
        if ($names[0] == 404) {
            Builder::show404();
        }
        Builder::show404();
    }

    /**
     *   Обработка исключения Http
     */
    static function process_http_exception(HttpException $exception) {
        ob_end_clean();

        switch (get_class($exception)) {
            case 'Http404':
                Builder::show404();
                break;
            case 'HttpRedirect':
                header($exception->getHttpHeader(), true, $exception->getHttpCode());
                break;
        }

        return;
    }

    /**
     * Завершение выполнения скрипта с кодом 404.
     * Мимикрия под Apache.
     */
    static function show404($uri = null) {
        if (is_null($uri)) {
            $uri = $_SERVER['REQUEST_URI'];
        }

        header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found", true, 404);

        print new View('_errors/404');
        exit;
    }

    static function show403() {
        print new View('_errors/403');
        exit;
    }

    /**
     * 	Обработчик фатальных ошибок.
     */
    static function fatal_error_handler($errno, $errstr, $errfile, $errline) {
        // Сбрасываем все буферы вывода, которые есть
        while (ob_get_level()) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            header("Content-Type: text/html;charset=utf-8");
        }
        if (self::$developmentMode) {
            if (strstr($errstr, '<html>') === false) {
                echo "<html><h2>" . $errstr . "</h2><h4>" . Meta::getPhpErrorName($errno) . " in {$errfile} on line {$errline}.</h4>";
                $trace = debug_backtrace();
                array_shift($trace);
                echo Meta::formatDebugTrace($trace);
                echo "</html>";
            } else {
                echo $errstr;
            }
        } else {
            if (strstr($errstr, '<html>') === false) {
                echo new View('_errors/fatal-error', array('message' => $errstr));
            } else {
                echo $errstr;
            }
        }
        exit;
    }

    /**
     * 	Обработчик неперехваченных исключений.
     */
    static function uncaught_exception_handler($exception) {
        // Сбрасываем все буферы вывода, которые есть
        while (ob_get_level()) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            header("Content-Type: text/html;charset=utf-8");
        }

        if (self::$developmentMode) {
            echo "<html><h2>" . $exception->getMessage() . "</h2><h4>" . get_class($exception) . " in " . $exception->getFile() . " on line " . $exception->getLine() . ".</h4>";
            echo Meta::formatDebugTrace($exception->getTrace());
            echo "</html>";
        } else {
            echo new View('_errors/fatal-error', array('message' => $exception->getMessage()));
        }
        exit;
    }

    /**
     * 	Постобработка кода страницы
     */
    static function postprocess($text) {
        // Добавляем lang-атрибут для текущего языка
        $text = str_replace('<html', '<html lang="' . NamiCore::getLanguage()->name . '"', $text);

        // Если включен язык, отличный от default — переписываем все ссылки кроме static
        if (self::$language) {
            /*
              // TODO: не удалять эту строку, пока не обкатаем на вялом VPS
              $text = str_repeat( $text, 10 );
             */
            $text = preg_replace('~("/)((?!static|' . self::$langRegExp . ')[^"]*")~S', '$1' . self::$language . '/$2', $text);
            /*
              $text = preg_replace( '~("/)((?!static|'.self::$langRegExp.')[^"]*")~S', '$1'.self::$language.'/$2', $text, -1, $cnt );
              $end = microtime( 1 );
              printf( '%.9f %d, %9f', $end - $start, $cnt, ($end-$start)/$cnt );
             */
        }

        return $text;
    }

    static function getLanguages() {
        return preg_grep('/default/', array_keys(self::$languages), PREG_GREP_INVERT);
    }

    static function getLanguage() {
        return self::$language;
    }

    static function isCurrentLanguage($name) {
        return NamiCore::getLanguage()->name == mb_strtolower($name) ? true : false;
    }

    static function getAppUri($class) {
        if (!array_key_exists($class, self::$appUriCache)) {
            $page = Pages(array('type__app_classname' => $class))->first();
            self::$appUriCache[$class] = $page ? $page->uri : null;
        }
        return self::$appUriCache[$class];
    }

    static function getAppAttr($class, $attr) {
        if (!array_key_exists($class, self::$appCache)) {
            $page = Pages(array('type__app_classname' => $class))->first();
            self::$appCache[$class] = $page ? array('title' => $page->title, 'uri' => $page->uri, 'enabled' => $page->enabled) : null;
        }
        return self::$appCache[$class][$attr];
    }

    static function detectUserLang() {
        $locale = null;
        $locale_ = null;

        if (isset($_GET['lang'])) {
            $locale_ = $_GET['lang'];
            setcookie("builder_locale", $locale_, time() + 60 * 60 * 24 * 30, "/");  //30 дней
        } else if (isset($_COOKIE['builder_locale'])) {
            $locale_ = $_COOKIE['builder_locale'];
        }

        foreach (NamiConfig::$locales as $conf_locale) {
            if ($locale_ == substr($conf_locale, 0, strpos($conf_locale, "_"))) {
                $locale = $conf_locale;
            }
        }

        if (!$locale) {
            $locale = NamiConfig::$locales[0];
        }

        $lang = new NamiLanguage($locale);
        NamiCore::setLanguage($lang);
    }

}
