<?php

class CmsApplication {

    function __construct() {
        // Пользователя сессии нет
        if (!Session::getInstance()->getUser()) {
            $vars = Meta::vars();

            // Проверим, не переданы ли переменные из формы авторизации, и авторизуем пользователя, если это так
            if (array_key_exists('_builder_login', $vars) && array_key_exists('_builder_password', $vars)) {
                // Авторизуем
                if (Session::getInstance()->login($vars['_builder_login'], $vars['_builder_password'])) {
                    // Запрос, чтобы получить uri cms
                    $request = new CmsRequest();

                    // Если в настройках есть модуль по умолчанию, и произведен вход в корень сайта — редиректим
                    if (Meta::getUriPath() == $request->cmsUri &&
                            Session::getInstance()->getUser()->start_module &&
                            Session::getInstance()->getUser()->start_module->name) {
                        header("Location: {$request->cmsUri}/" . Session::getInstance()->getUser()->start_module->name . '/', true);
                        exit;
                    }
                }
            }
        }

        // Включаем нужный язык Nami
        if (Session::getInstance()->language) {
            try {
                NamiCore::setLanguage(Session::getInstance()->language);
            } catch (NamiException $e) {
                // При установке языка произошла ошибка, следовательно язык плохой, инициализируем его языком по умолчанию
                Session::getInstance()->language = NamiCore::getLanguage();
            }
        }
    }

    /**
      Точка входа приложения
     */
    function run() {
        // Запрос к административному разделу, он знает что к чему
        $request = new CmsRequest();

        // Проверим авторизованность пользователя, выдадим соответствующий ответ при отсутствии сессии
        if (!Session::getInstance()->getUser()) {
            if ($request->type == CmsRequest::MODULE_AJAX || $request->type == CmsRequest::MODEL) {
                print json_encode(array('success' => false, 'message' => 'Вы не авторизованы, или слишком долго бездейстовали. Пожалуйста, обновите страницу, Вам будет предложено ввести имя и пароль.'));
            } else {
                print new View('core/login-page', array('cmsUri' => $request->cmsUri));
            }
            exit;
        }


        //виджет подгрузки фоточек kcfinder
        //для работы требует перевенную сессии
        $_SESSION['KCFINDER'] = array("disabled" => false);

        //режим бога
        if (isset($_GET["develop"])) {
            if ($_GET["develop"] == "off") {
                $_SESSION['dev'] = false;
            } else {
                $_SESSION['dev'] = true;
            }
        }

        // Обрабатываем переключение языка
        if ($request->type == CmsRequest::LANG_SWITCH) {
            try {
                NamiCore::setLanguage($request->objectName);
                Session::getInstance()->language = NamiCore::getLanguage()->name;
            } catch (NamiException $e) {

            }

            $location = array_key_exists('HTTP_REFERER', $_SERVER) ? $_SERVER['HTTP_REFERER'] : $request->cmsUri;
            header("Location: {$location}", true);
            exit;
        } else if ($request->type == CmsRequest::MODEL) {
            // Обрабатываем MODEL

            $processor = new NamiRestProcessor();
            print $processor->process($request->objectName, $request->uri, Meta::vars());
            exit;
        }

        // Остались MODULE запросы. Получим активный модуль
        $module = $this->getModuleByName($request->objectName);

        // Имя есть, а модуль не нашелся? Непорядок, выдаем 404!
        if ($request->objectName && !$module) {
            self::show_404();
        }

        // В зависимости от типа запроса вызываем соответствующий обработчик
        if ($request->type == CmsRequest::MODULE_AJAX) {
            // ajax-запрос
            print $module ? $module->handleAjaxRequest($request->uri) : Builder::show404(Meta::getUriPath());
        } else {
            // Интерфейсный запрос — рисуем его в нашем интерфейсе :3
            $t = new View('core/common-page');

            $t->module = $request->objectName;
            $t->cmsUri = $request->cmsUri;
            $t->hint = ''; // TODO: сделать передачу подсказки из модуля сюда
            $t->moduleUri = $request->objectName;

            // Если есть активный модуль — рендерим его содержимое, иначе — страница приветствия
            if (!$module) {
                $module = new WelcomeModule("/");
            }

            $t->hint = $module->getHint();
            $t->hideMenu = $module->getHideMenu();

            // Проверим наличие title, если нет — выбран встроенный модуль, и тайтл тоже нужно приготовить самостоятельно
            if (!$t->has('title')) {
                switch ($request->objectName) {
                    case 'settings':
                        $t->title = 'Настройка';
                        break;
                    case 'password':
                        $t->title = 'Cмена пароля';
                        break;
                    case 'help':
                        $t->title = 'Помощь';
                        break;
                    default:
                        if (Meta::getUriPath() == "/cms") {
                            $t->title = 'Добро пожаловать!';
                        } else {
                            $t->title = $module->title;
                        }
                        break;
                }
            }

            // Позволим модулю обработать запрос и вернуть контент
            $t->content = $module->handleHtmlRequest($request->uri);

            // Шаблон заполнен, выводим его
            print $t;
        }

        return true;
    }

    static function show_404() {
        $tpl = new View("core/404");
        $tpl->hint = null;

        print $tpl;
        exit();
    }

    static function is_develop_mode() {
        if (isset($_SESSION['dev'])) {
            if ($_SESSION['dev']) {
                return true;
            }
        }

        return false;
    }

    /**
      Получение модуля по имени
      $name - имя модуля
      Возвращает объект-модуль или null, если таковой не обнаружен
     */
    protected function getModuleByName($name) {
        // Сначала проверяем встроенные модули административного интерфейса
        switch ($name) {
            case 'logout':
                return new LogoutModule($name);

            case 'settings':
                return new SettingsModule($name);

            case 'password':
                return new PasswordModule($name);

            case 'help':
                return new HelpModule($name);
        }

        $module = Modules()
                ->filter(array(
                    'name' => $name,
                    'enabled' => true,
                    'id__in' => Session::getInstance()->getUser()->getAvaliableModulesIds()
                ))
                ->first();

        return $module;
    }

}
