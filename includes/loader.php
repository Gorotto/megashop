<?php

require_once 'defined.php';
require_once 'conf_db.php';
require_once 'conf_models.php';
require_once 'conf_locales.php';

//класс для управления автоинклудами
require_once DIR_INC . '/core/NamiAutoloadClass.class.php';

//пути до директорий с автоинклудами
$include_paths = [
    DIR_INC . "/3rdparty",
    DIR_INC . "/controllers",
    DIR_INC . "/core",
    DIR_INC . "/models",
    DIR_INC . "/nami",
    DIR_INC . "/utilites",
    DIR_ROOT . "/cms/includes"
];

// Активируем наши инклуды
set_include_path(join(PATH_SEPARATOR, array_merge($include_paths, [get_include_path()])));
// Активируем автолоад
spl_autoload_register(array('NamiAutoloadClass', 'autoload'));


// Выключаем magic_quotes_runtime, чтобы они не портили нам данные из БД
if (get_magic_quotes_runtime()) {
    set_magic_quotes_runtime(false);
}

// Настраиваем конфигурацию
NamiConfig::$db_backend = "Nami{$db_params['db_backend']}DbConnection";
NamiConfig::$db_mapper = "Nami{$db_params['db_backend']}DbMapper";
NamiConfig::$db_host = $db_params['db_host'];
NamiConfig::$db_port = $db_params['db_port'];
NamiConfig::$db_name = $db_params['db_name'];
NamiConfig::$db_user = $db_params['db_user'];
NamiConfig::$db_password = $db_params['db_password'];
NamiConfig::$db_charset = $db_params['db_charset'];
NamiConfig::$db_prefix = $db_params['db_prefix'];
NamiConfig::$db_debug = $db_params['db_debug'];

// Доступные локали
NamiConfig::$locales = $locales;

// Модели
NamiConfig::$models = $model_names;

// Регистрируем утилиты, и собственно этим и запускаем Nami в работу
NamiCore::getInstance()->registerUtilities();
