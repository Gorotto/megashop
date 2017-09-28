<?php

class WelcomeModule extends AbstractModule {

//    protected $hint = '<p>В <a href="./settings/">настройках</a> вы можете выбрать модуль, который используете чаще всего, и он будет открываться сразу после входа в систему.</p>';
    protected $hideMenu = true;
    protected $uriconf = array(
        array('~^/?$~', 'index'),
    );

    function index($vars, $uri) {
        if (Meta::vars("show_phpinfo")) {
            phpinfo();
            die;
        }

        $modules = Modules()
            ->filter(array(
                'id__in' => Session::getInstance()->getUser()->getAvaliableModulesIds(),
                'name__ne' => 'customeditor',
            ))
            ->sortedOrder()
            ->all();

        return $this->getView('index', compact('modules'));
    }

}
