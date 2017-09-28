<?

class User extends NamiModel {

    static function definition() {
        return array(
            'login' => new NamiCharDbField(array('localized' => false, 'maxlength' => 50, 'null' => false, 'index' => 'login')),
            'password' => new NamiCharDbField(array('localized' => false, 'maxlength' => 32, 'null' => false, 'index' => 'login')),
            'start_module' => new NamiFkDbField(array('model' => 'Module', 'related' => 'users')),
            'avaliable_modules_ids' => new NamiCharDbField(array('maxlength' => 250)),
        );
    }

    public $description = array(
        'login' => array('title' => 'Логин'),
        'password' => array('title' => 'Пароль'),
    );

    function construct() {
        //заполняем список доступных модулей
        //системные модуля доступны только admin и metaroot
        $modules_list = array();
        $modules = Modules()
                ->filter(array(
                    "enabled" => true,
                    "system" => false
                ))
                ->sortedOrder()
                ->values(array("id", "title"));

        if ($modules) {
            foreach ($modules as $module) {
                $modules_list[$module['id']] = $module['title'];
            }
        }

        $this->description['avaliable_modules_ids'] = array(
            "title" => "Доступные модули",
            "widget" => "chosen",
            "choices" => $modules_list
        );
    }

    function beforeSave() {
        $this->login = Meta::getPathName($this->login);

        if (Users(array('login' => $this->login, 'id__ne' => $this->id))->count()) {
            throw new NamiValidationException("Пользователь '{$this->login}' уже существует", $this->meta->getField('login'));
        }

        if (!preg_match('/^[a-f0-9]{32}$/i', $this->password)) {
            $this->password = md5($this->password);
        }
    }

    function beforeDelete() {
        if ($this->id == 1) {
            throw new Exception('Удаление учетной записи администратора запрещено.');
        }
    }

    function getAvaliableModulesIds() {
        if ($this->login == "admin" || $this->login == "metaroot") {
            $modules_filter = array("enabled" => true, 'system' => false);
            if (CmsApplication::is_develop_mode()) {
                unset($modules_filter['system']);
            }

            return Modules()->filter($modules_filter)->values('id');
        } else {
            return json_decode($this->avaliable_modules_ids);
        }
    }

}
