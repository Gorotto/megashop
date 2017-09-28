<?php

/**
  Процессор REST-запросов к моделям шторма.
  Нужно очень аккуратно разграничивать доступ к этому интерфейсу, ибо он позволяет редактировать любую модель сайта.
 */
class NamiRestProcessor {

    // Массив имен моделей, которые нельзя редактировать через процессор
    protected $disallowedModels = array(
//        'MySecretModelName',
    );
    // Массив разрешенных моделей. Если массив пуст — разрешены все.
    protected $allowedModels = array();

    /**
     *   Конструктор.
     *   $allowed — массив имен моделей, к которым открыт доступ.
     */
    function __construct($allowed = array()) {
        $this->allowedModels = $allowed;
    }

    /**
     *   Обработка запроса
     *   $model - имя модели
     *   $uri - путь запроса, например '/5/update/'
     *   $vars - переменные, переданные вместе с запросом (обычно это Meta::vars())
     */
    function process($model, $uri, $vars) {
        try {
            // Проверим данные
            if (!( class_exists($model) && is_subclass_of($model, 'NamiModel') )) {
                throw new Exception("Класс {$model} не является моделью и не может быть обработан.");
            }

            //модель user может редактироваться в случаях
            //когда админ имеет доступ к модулю пользователи сайта
            //при этом учетные записи metaroot и admin нельзя изменить
            //из под другого логина (проверяется в методе update, delete)

            if ($model == "User") {
                $users_module = Modules()->get(array("classname" => "UserModule"));
                if ($users_module) {
                    $avaliable_modules_ids = Session::getInstance()->getUser()->getAvaliableModulesIds();
                    if (!in_array($users_module->id, $avaliable_modules_ids)) {
                        throw new Exception("У вас нет доступа к модулю «Пользователи CMS».");
                    }
                } else {
                    throw new Exception("Доступ к модели User осуществляется только через модуль UserModule.");
                }
            } else if (
                    in_array($model, $this->disallowedModels) ||
                    (count($this->allowedModels) > 0 && !in_array($model, $this->allowedModels))
            ) {
                throw new Exception("Доступ к модели {$model} запрещен.");
            }

            // Ответные данные запроса
            $data = null;

            // Получим путь, чтобы понять что делать
            $names = Meta::getUriPathNames($uri);

            if (!$names) {
                // Если в uri ничего не указано — возвращаем структуру модели

                $object = new $model;
                $data = $object->asArray(true);
            } elseif (array_key_exists(0, $names)) {
                // Действия над моделью и множеством объектов

                switch ($names[0]) {

                    case 'create':
                        //обработка данных от AjaxUploader
                        if (array_key_exists("is_file_upload", $vars)) {
                            if (!array_key_exists("replace_field", $vars) ||
                                    !array_key_exists("files", $vars)) {
                                throw new Exception("Неверный формат данных (ajaxupload plugin)");
                            }

                            $params[$vars['replace_field']] = $vars["files"][0];
                            $data = NamiQuerySet($model)->create($params)->asArray();
                        } else {
                            $new_item = NamiQuerySet($model)->create(( count($vars) == 1 && array_key_exists('json_data', $vars) ) ? json_decode($vars['json_data'], true) : $vars );

                            //hack для модели CustomPublicationBlock нужно вернуть сформированный хтмл
                            if ($model == "CustomPublicationBlock") {
                                $data = [
                                    "id" => $new_item->id,
                                    "html" => $new_item->html,
                                    "enabled" => $new_item->enabled,
                                ];
                            } else {
                                $data = $new_item->asArray();
                            }
                        }
                        break;

                    //TODO переделать
                    //метод вообще ломаный. неверная логика работы
                    //отличается от всех остальных. Т.к. в 100% случаев используется
                    //для реализации интерфейса массового загрузчика
                    //можно сделать пару костылей. но лучше переделать js
                    case 'create_many':
                        $data = array();

                        // т.к. загружаются файлы через createmany, то add_info всегда должен иметь инфо
                        // и при его отсутствии можно понять, что файл не загрузился
                        $min_size = min(ini_get('post_max_size'), ini_get('upload_max_filesize'));
                        if (!$vars) {
                            throw new Exception("Не удалось загрузить файл, скорее всего это связано с ограничениями хостинга.\r\nМаксимальный размер файла не должен превышать {$min_size}.");
                        }

                        // данные от AjaxUploader'a отличаются своей структурой.
                        // плагин не может связать отправляемые файлы и доп данные,
                        // поэтому на клиенте нужно указать поле к которому
                        // будут добавлены данные
                        if (array_key_exists("is_file_upload", $vars)) {
                            if (!array_key_exists("replace_field", $vars) ||
                                    !array_key_exists("files", $vars)) {
                                throw new Exception("Неверный формат данных");
                            }

                            foreach ($vars["files"] as $file) {
                                $params = array(
                                    $vars['replace_field'] => $file
                                );

                                if (array_key_exists("additional_data", $vars)) {
                                    foreach (json_decode($vars['additional_data']) as $key => $value) {
                                        $params[$key] = $value;
                                    }
                                }

                                $data[] = NamiQuerySet($model)->create($params)->asArray();
                            }
                        } else {
                            if (!array_key_exists("json_data", $vars)) {
                                throw new Exception("Неверный формат данных");
                            }

                            $items = json_decode($vars['json_data'], true);

                            foreach ($items as $item_params) {
                                $data[] = NamiQuerySet($model)->create($item_params)->asArray();
                            }
                        }

                        break;

                    case 'update':
                        $objects = json_decode($vars['objects'], true);
                        foreach (NamiQuerySet($model)->filter(array('pk__in' => array_keys($objects)))->all() as $o) {
                            $o->copyFrom($objects[$o->meta->getPkValue()])->save();
                            unset($objects[$o->meta->getPkValue()]);
                        }
                        $this->checkUnusedObjects($model, $objects);
                        break;

                    case 'delete':
                        $objects = array();
                        foreach (json_decode($vars['objects'], true) as $id) {
                            $objects[$id] = $id;
                        }
                        foreach (NamiQuerySet($model)->filter(array('pk__in' => $objects))->all() as $o) {
                            $o->delete();
                            unset($objects[$o->meta->getPkValue()]);
                        }
                        $this->checkUnusedObjects($model, $objects);
                        break;

                    case 'reorder':
                        $qs = NamiQuerySet($model);
                        if (!method_exists($qs, 'reorder')) {
                            throw new Exception("Модель {$model} невозможно упорядочить.");
                        }
                        $qs->reorder(json_decode($vars['objects'], true));
                        break;

                    case 'retrieve':

//                        query должен быть JSON-закодированным массивом следующего вида
//                        [
//                            {                       // первый вызов
//                                filter:             // имя метода
//                                [                   // массив аргументов
//                                    'position__lt', // первый аргумент
//                                    4               // второй аргумент
//                                ]
//                            },
//                            {                       // второй вызов
//                                order:              // имя метода
//                                [                   // массив аргументов
//                                    'position'      // первый аргумент
//                                ]
//
//                            },
//                            {                       // третий вызов
//                                limit:              // имя метода
//                                [                   // массив аргументов
//                                    10              // первый аргумент
//                                ]
//                            }
//                        ]
//                        Вся эта структура преобразуется в последовательные запросы к соответствующему NamiQuerySet-у.
//                        Результат обрабатывается методом getJsonSafe и возвращается.
//                        Можно применять финальные методы limit, first, all, tree, а можно и не применять — автоматически применится all.
//                        Структура возвращается максимально соответствующая запросу — asArray( true ) для объектов, массивы для массивов.
//

                        $query = NamiQuerySet($model);

                        if ($vars['query']) {
                            // Фильтруем, фильтруем, фильтруем
                            foreach (json_decode($vars['query'], true) as $part) {

                                foreach ($part as $method => $args) {

                                    if (!is_object($query)) {
                                        throw new Exception("Метод {$method} не может быть вызван, так как текущий запрос ({$query}) не является объектом.");
                                    } elseif (!method_exists($query, $method)) {
                                        throw new Exception("Метод {$method} не найден в классе " . get_class($query) . ".");
                                    }

                                    $query = call_user_func_array(array($query, $method), $args);
                                }
                            }
                        }

                        // Нафильтровали! Теперь смотрим, что получилось, переводим его в array или что-то подобное и готово.
                        $data = $this->getJsonSafe($query);
                        break;

                    default:
                        // Действия над конкретным элементом модели, идентифицируемым по значению PK

                        if (is_numeric($names[0])) {
                            if (!array_key_exists(1, $names)) {
                                $names[1] = '';
                            }
                            switch ($names[1]) {
                                case 'update':
                                    //учетные записи metaroot и admin нельзя изменить
                                    //из под другого логина
                                    if ($model == "User") {
                                        $edit_user = $this->getModelObject($model, $names[0]);
                                        if ($edit_user->login == "admin" || $edit_user->login == "metaroot") {
                                            $cur_user = Session::getInstance()->getUser();
                                            if ($cur_user->login != "admin" || $cur_user->login != "metaroot") {
                                                throw new Exception("Редактирование учетной записи запрещено.");
                                            }
                                        }
                                    }


                                    $upd_item = $this->getModelObject($model, $names[0])->copyFrom(( count($vars) == 1 && array_key_exists('json_data', $vars) ) ? json_decode($vars['json_data'], true) : $vars )->save();

                                    //hack для модели PublicationBlock нужно вернуть сформированный хтмл
                                    if ($model == "CustomPublicationBlock") {
                                        $data = [
                                            "id" => $upd_item->id,
                                            "html" => $upd_item->html,
                                            "enabled" => $upd_item->enabled,
                                        ];
                                    } else {
                                        $data = $upd_item->asArray();
                                    }

                                    break;

                                case 'delete':
                                    //учетные записи metaroot и admin нельзя изменить
                                    //из под другого логина
                                    if ($model == "User") {
                                        $edit_user = $this->getModelObject($model, $names[0]);
                                        if ($edit_user->login == "admin" || $edit_user->login == "metaroot") {
                                            $cur_user = Session::getInstance()->getUser();
                                            if ($cur_user->login != "admin" || $cur_user->login != "metaroot") {
                                                throw new Exception("Редактирование учетной записи запрещено.");
                                            }
                                        }
                                    }

                                    $this->getModelObject($model, $names[0])->delete();
                                    break;

                                case 'copy':
                                    $data = $this->getModelObject($model, $names[0])->createCopy();
                                    break;

                                case 'retrieve':
                                    $data = $this->getModelObject($model, $names[0])->asArray(true);
                                    break;

                                default:
                                    $data = $this->getModelObject($model, $names[0])->asArray();
                                    break;
                            }
                        }
                        break;
                }
            }

            return json_encode(array('success' => true, 'data' => $data));
        } catch (Exception $e) {
            return json_encode(array(
                'success' => false,
                'message' => $e->getMessage(),
            ));
        }
    }

    /**
     *   Получение объекта модели
     */
    protected function getModelObject($model, $id) {
        $object = NamiQuerySet($model)->get($id);

        if (!$object) {
            throw new Exception("Объект {$model} с идентификатором {$id} не найден.");
        }

        return $object;
    }

    /**
     *   Проверка пустоты массива данных объектов. Если в массиве есть элементы, выбрасывается Exception
     *   с сообщением, что имеющиеся в массиве объекты не найдены.
     */
    protected function checkUnusedObjects($model, array $objects) {
        if ($objects) {
            throw new Exception(
            count($objects) > 1 ?
                    "Объекты {$model} с идентификаторами " . join(', ', array_keys($objects)) . " не найдены." :
                    "Объект {$model} с идентификатором " . join(', ', array_keys($objects)) . " не найден."
            );
        }
        return true;
    }

    /**
     *   Перевод чего-то в JSON-безопасную форму.
     *   $something можут быть строкой, массивом, массивом объектов, QuerySet-ом, моделью %D
     */
    protected function getJsonSafe($something) {

        $result = null;

        if (is_object($something)) {

            if ($something instanceof NamiModel) {
                $result = $something->asArray(true);
            } elseif ($something instanceof NamiQuerySet) {
                $result = $this->getJsonSafe($something->all());
            } else {
                throw new Exception("Cannot make " . get_class($something) . " object JSON-safe.");
            }
        } elseif (is_array($something)) {
            $result = array();
            foreach ($something as $k => $v) {
                $result[$k] = $this->getJsonSafe($v);
            }
        } else {
            $result = $something;
        }

        return $result;
    }

}
