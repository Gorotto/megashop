<?

/**
 * NamiQuerySet class.
 * Запрашивалка данных модели.
 */
class NamiQuerySet {

    protected static $cache = array();
    protected $model;   // Модель, по которой выполняем выборки
    protected $qc;  // Условия выборки
    protected $distinct = false; // Признак distinct-выборки
    protected $order = array();  // Поля сортировки, каждый элемент — массив с ключами field и order (asc или desc)
    protected $follow = 0;  // Проследование вглубь модели при выборках
    protected $skipFields = array(); // массив имен полей, данные которых не следует выбирать. TODO: переделать в отложеные поля, загружающиеся по обращению из модели.
    protected $onlyFields = array(); // массив имен полей, данные которых и только их будут выбраны. Из этого массива можно исключить поля методом skip()
    protected $limit = null;

    /**
      Конструктор.
      $model -  имя модели, для которой конструируется NamiQuerySet.
     */
    function __construct($model) {
        // Проверим имя модели
        if (!( class_exists($model) && is_subclass_of($model, 'NamiModel') )) {
            throw new NamiException("$model is not a Nami model");
        }

        // Установим имя своей модели
        $this->model = $model;

        // Условия по умолчанию — выбираем все записи в базе
        $this->qc = new NamiQCALL();
    }

    /**
      Получение копии NamiQuerySet-а
     */
    function __clone() {
        // Клонируем проверки, остальное склонируется само
        $this->qc = clone $this->qc;
    }

    /**
      Объединение двух массивов объектов NamiQueryJoin.
      Проверяет, чтобы не было повторов по алиасам. Первый массив — ведущий.
      Возвращает объединенный массив.
     */
    protected function mergeJoins($joins1 = array(), $joins2 = array()) {
        if (array_key_exists('last', $joins1) && $joins1['last']) {
            unset($joins1['last']);
        }

        if (array_key_exists('last', $joins2) && $joins2['last']) {
            unset($joins2['last']);
        }

        // Замаппируем имеющиеся join-ы
        $existing = array();
        foreach ($joins1 as $j) {
            $existing[] = $j->alias;
        }

        foreach ($joins2 as $j) {
            if (!in_array($j->alias, $existing)) {
                $existing[] = $j->alias;
                $joins1[] = $j;
            }
        }

        return $joins1;
    }

    /**
      Функция генерации SQL-запроса выборки данных
      Аргументы:
      $type - типа запроса, 'values' - значения, 'count' - подсчет количества записей
      $extra - массив, дополнительные данные для указанного типа запроса,
      для 'values' это 'limit' и 'offset'
      Возвращает массив ( sql=>'...', params=>array(...), fields=>'...' )
      sql - текст запроса
      params - параметры, которыми следует заполнить sql для выполнения
      fields - массив соответствия alias-ов полей, которые будут выбраны запросом
     */
    function getNamiQueryParts($type = 'values', array $extra = array()) {
        /* Так как follow подключает связанные таблицы, которые могут ограничить нашу выборку, то использование
          всех join-ов обязательно в обоих типах запросов — values и count */

        // Выберем данные полей запроса
        $fdata = $this->getNamiQueryEntities();


        // Вытащим из частей запроса массив Join-ов
        $Joins = array();
        foreach ($fdata as $part) {
            if ($part->join) {
                $Joins[] = $part->join;
            }
        }

        // Преобразуем условия выборки в SQL форму
        $qc = $this->qc->getNamiQueryParts($this->model, $this);

        // Добавим join-ы
        $Joins = $this->mergeJoins($Joins, $qc['joins']);

        // Часть where
        $Where = $qc['where'] ? "WHERE {$qc['where']}" : '';

        // Главная таблица и ее алиас
        $Alias = NamiCore::getMapper()->getModelAlias($this->model);
        $Table = NamiCore::getMapper()->getModelTable($this->model);

        // Флаг distinct-выборки
        $Distinct = $this->distinct ? 'DISTINCT' : '';

        // Поля выборки (будут инициализированы позднее)
        $Fields = '';

        // Сортировка
        $Order = '';

        // Ограничение
        $Limit = '';

        // Дальше запросы отличаются: для values нужно сделать выборку полей, а для count — нет :)
        switch ($type) {
            // Удаление данных
            case 'delete':
                // TODO разрулить удаление записей при наличии Join-ов
                if ($Joins) {
                    throw new NamiException("delete() is not implemented on multi-model (having join lookups) querysets. Single-model queryset must be used.");
                }

                // Запрещаем удалять все записи из таблицы
                if (!$Where) {
                    throw new NamiException("Cannot use delete() on all() queryset.");
                }

                // TODO отвратительный хак, для избавления от него нужно переработать qc->getNamiQueryParts так, чтобы можно было получить список полей в where без алиасов таблиц
                $Where = str_replace("{$Alias}.", "", $Where);

                break;

            // Запрос — получение данных
            case 'raw_values':
            case 'values':
                // Подготовим список полей
                $FieldList = array();

                if ($type == 'raw_values') {
                    foreach ($extra['fields'] as $name) {
                        $field = $this->resolveCheckField($name);
                        $FieldList[] = "{$field['alias']}.{$field['columnName']} as {$this->model}__{$name}";
                    }
                } else {
                    foreach ($fdata as $i) {
                        $FieldList = array_merge($FieldList, $i->fields);
                    }

                    if ($qc['expressions']) {
                        $entities = array();
                        foreach ($qc['expressions'] as $alias => $expression) {
                            $FieldList[] = "{$expression} as {$alias}";
                        }
                    }
                }

                $Fields = join(', ', $FieldList);

                // Инициализируем limit и offset, если они еще не указаны
                if (!array_key_exists('limit', $extra)) {
                    $extra['limit'] = 0;
                }
                if (!array_key_exists('offset', $extra)) {
                    $extra['offset'] = 0;
                }

                // Получим limit-строку
                $Limit = NamiCore::getMapper()->getLimitOffsetSql($extra['limit'], $extra['offset']);

                // Определимся с Order
                if (count($this->order) > 0) {
                    $orders = array();

                    /*  Пройдемся по полям, которые переданы для упорядочивания по ним результата.
                      Некоторые из них мы можем понять сразу, другие - нет */
                    foreach ($this->order as $o) {
                        if ($o['field'][0] == '-') {
                            // Поля, начинающиеся с минуса передаем как есть, только без минуса
                            $orders[] = mb_substr($o['field'], 1, mb_strlen($o['field'], 'utf-8'), 'utf-8') . " {$o['order']}";
                        } else if (mb_strpos($o['field'], '()', 0, 'utf-8') !== false) {
                            // Вызовы функций
                            $orders[] = "{$o['field']}";
                        } else if (mb_strpos($o['order'], 'FIELD(_', 0, 'utf-8') !== false) {
                            // order by field обработал отдельно, т.к. ссу трогать эту шаткую конструкцию.
                            $field_info = $this->parseOrderField($o['field']);
                            $orders[] = str_replace("_", $field_info['field'], $o['order']);
                        } else {
                            // Все остальные поля могут быть сложными, и их нужно разбирать отдельно
                            $field_info = $this->parseOrderField($o['field']);
                            if (array_key_exists('joins', $field_info)) {
                                $Joins = $this->mergeJoins($Joins, $field_info['joins']);
                            }
                            $orders[] = "{$field_info['field']} {$o['order']}";
                        }
                    }

                    $Order = count($orders) ? "ORDER BY " . join(', ', $orders) : '';
                }
                break;

            case 'count':
                $Fields = 'count(*) as count';
                break;

            default: throw new NamiException("Unknown query type '{$type}'");
        }

        // Соединим join-ы
        $Joins = join(' ', $Joins);

        // Сгенерируем текст запроса
        switch ($type) {
            case 'delete':
                $sql = "DELETE FROM $Table $Where";
                break;

            default:

                $sql = "SELECT $Distinct $Fields FROM $Table AS $Alias $Joins $Where $Order $Limit";
                break;
        }

        return array('sql' => $sql, 'params' => $qc['params'], 'fields' => $fdata);
    }

    public function parseOrderField($field) {
        $info = array();

        // Сложные поля разберем
        if ($field[0] === '(') {
            // Выражения - сделаем замену регуляркой >_>
            $replacer = new ParseOrderReplacer($this);
            $processed = preg_replace_callback('/[a-z_]+/i', array($replacer, 'process'), $field);
            $info['field'] = $processed;
            if (count($replacer->joins)) {
                $info['joins'] = $replacer->joins;
            }
        } else if (mb_strpos($field, '__', 0, 'utf-8') !== false) {
            $cf = new NamiComplexField($this->model, $field);
            $info['field'] = $cf->field;
            $info['joins'] = $cf->joins;
        } else {
            // Простые добавим как есть
            $db_field = NamiCore::getInstance()->getNamiModelMetadata($this->model)->getField($field);
            $alias = NamiCore::getMapper()->getModelAlias($this->model);
            $info['field'] = "{$alias}." . NamiCore::getMapper()->getFieldColumnName($db_field);
        }

        return $info;
    }

    /**
      Упорядочивание записей
      Принимает массив или строку, типа
      Pieces()->orderAsc( 'name' )
      Pieces()->orderDesc( array( 'name', 'value' ) )
      Pieces()->order( 'colour__name' )   — комплексные поля тоже позволительны, да :3 о join-ах позаботится штормоядро
      order эквивалентна orderAsc
     */
    protected function _order(array $fields, $order) {
        foreach ($fields as $name) {
            $this->order[] = array('field' => $name, 'order' => $order);
        }
    }

    /**
      Сортировка по возрастанию
     */
    function order($params) {
        return $this->orderAsc($params);
    }

    /**
      Сортировка по возрастанию
     */
    function orderAsc($params) {
        $next = clone $this;
        $next->_order(is_array($params) ? $params : array($params), 'asc');
        return $next;
    }

    /**
      Сортировка по убыванию
     */
    function orderDesc($params) {
        $next = clone $this;
        $next->_order(is_array($params) ? $params : array($params), 'desc');
        return $next;
    }

    function orderRand() {
        $next = clone $this;
        $next->_order(array("RAND()"), null);
        return $next;
    }

    function orderByField($fieldName, $values) {
        if (is_array($values)) {
            $values = implode(",", $values);
        }
        $next = clone $this;
        $orderTxt = "FIELD(_, {$values})";
        $next->_order(array($fieldName), $orderTxt);
        return $next;
    }

    function orderRelevant() {
        $next = clone $this;
        $next->_order(array("-fulltext_relevance"), 'desc');
        return $next;
    }

    // Переключение проследования
    function follow($depth) {
        $next = clone $this;
        $next->follow = $depth;
        return $next;
    }

    // Пропуск загрузки полей
    function skip($fields) {
        if (!is_array($fields)) {
            $fields = array($fields);
        }

        $next = clone $this;
        $next->skipFields = array_merge($next->skipFields, $fields);
        return $next;
    }

    // Выборка определенных полей
    function only($fields) {
        $next = clone $this;

        if ($fields === false) {
            $fields = array();
        } else {
            if (!is_array($fields)) {
                $fields = explode(' ', $fields);
            }
            $fields = array_unique(array_merge($next->onlyFields, $fields));
        }

        $next->onlyFields = $fields;
        return $next;
    }

    // запрос записей по параметрам
    protected function query($params, $embrace = false) {
        // Клонируем себя, чтобы остаться неизменным
        $next = clone $this;

        // Добавляем параметров следующему в цепочке NamiQuerySet-у
        if ($params) {
            $qc = $params instanceof NamiQC ? $params : new NamiQC($params);
            $next->qc = $next->qc ? ( $embrace ? QOR($next->qc, $qc) : QAND($next->qc, $qc) ) : $qc;
        }

        // Возвращаем следующий объект
        return $next;
    }

    // Фильтрация записей
    function filter($params) {
        return $this->query($params, false);
    }

    // Фильтрация записей
    function filterInJoin($tableToJoin, $fieldToLink, $filteredField, $filteredValue) {
        $next = clone $this;

        $qc = new NamiQCFilterInJoin($tableToJoin, $fieldToLink, $filteredField, $filteredValue);
        $next->qc = $next->qc ? QAND($next->qc, $qc) : $qc;
        $next->distinct = true;

        return $next;
    }

    // Добавление записей, ограниченых переданными условиями.
    // Отличается от filter условием OR вместо AND.
    function embrace($params) {
        return $this->query($params, true);
    }

    /**
      Получение списка сущностей, которые будут участвовать в текущем запросе.
      Возвращает массив объектов NamiQueryEntity в нужном порядке
     */
    protected function getNamiQueryEntities($model = null, $depth = 0, $path = array(), $supalias = null, $null = false) {
        // Проверим, не ушли ли мы уже дальше, чем следует
        if ($depth > $this->follow) {
            return array();
        }

        $core = NamiCore::getInstance();

        // Тут будем накапливать результат!
        $entities = array();
        $alias = null;

        // Получим метаданные текущей обрабатываемой модели
        $meta = $core->getNamiModelMetadata($model ? $model : $this->model );

        // Следующая часть запроса
        $part = new NamiQueryEntity($model ? $model : $this->model );

        if ($model) {
            // Мы в глубине
            $part->path = $path;

            $supfield = $path[count($path) - 1];
            $alias = "{$supalias}__{$supfield}";

            $part->join = new NamiQueryJoin(
                $null ? 'LEFT' : 'INNER', $core->mapper->getModelTable($model), $alias, "{$supalias}.{$supfield} = {$alias}.{$meta->pkname}"
            );


            foreach ($meta->getFields() as $n => $v) {
                if (!$v instanceof NamiFkDbField || $depth == $this->follow) {
                    if ($v->localized) {
                        foreach (NamiCore::getAvailableLanguages() as $language) {
                            $falias = join('__', array_merge(array($this->model), $path, array($n))) . "__{$language->name}";
                            $part->fields[] = "{$alias}." . NamiCore::getMapper()->getFieldColumnName($v, $language) . " AS $falias";
                            $part->aliases[$n][$language->name] = "$falias";
                        }
                    } else {
                        $falias = join('__', array_merge(array($this->model), $path, array($n)));
                        $part->fields[] = "{$alias}." . NamiCore::getMapper()->getFieldColumnName($v) . " AS $falias";
                        $part->aliases[$n] = "$falias";
                    }
                }
            }
        } else {
            // Мы в начале, разбираем основную сущность запроса
            $alias = $core->mapper->getModelAlias($this->model);

            foreach ($meta->getFields() as $n => $v) {
                // Выбираем поле только в тех случаях, если массив определенных к выборке полей пуст,
                // либо поле явно определено к выборке и его имя есть в массиве onlyFields,
                // либо это первичный ключ
                if (empty($this->onlyFields) || in_array($n, $this->onlyFields) || $v instanceof NamiAutoDbField) {

                    // Пропустим поля, которые не нужно загружать TODO: сделать defered
                    if (in_array($n, $this->skipFields)) {
                        continue;
                    }


                    if (!$v instanceof NamiFkDbField || $depth == $this->follow) {
                        if ($v->localized) {
                            foreach (NamiCore::getAvailableLanguages() as $language) {
                                $falias = "{$this->model}__{$n}__{$language->name}";
                                $part->fields[] = "{$alias}." . NamiCore::getMapper()->getFieldColumnName($v, $language) . " AS $falias";
                                $part->aliases[$n][$language->name] = $falias;
                            }
                        } else {
                            $falias = "{$this->model}__{$n}";
                            $part->fields[] = "{$alias}." . NamiCore::getMapper()->getFieldColumnName($v) . " AS $falias";
                            $part->aliases[$n] = $falias;
                        }
                    }
                }
            }
        }

        $entities[] = $part;

        // Идем по полям текущей модели дальше в рекурсию
        foreach ($meta->getFields() as $n => $v) {
            if ($v instanceof NamiFkDbField) {
                $more_entities = $this->getNamiQueryEntities($v->model, $depth + 1, array_merge($path, array($n)), $alias, $v->null || $null);
                $entities = array_merge($entities, $more_entities);
            }
        }

        return $entities;
    }

    /**
     *   Выборка записей в виде простых значений (массив массивов)
     *   $fields - может быть массивом имен полей или строкой - именем поля.
     *
     *   TODO: вложенность по внешним ключам не работает!
     *   Поля других моделей поддерживаются, если указать у поля характеристики fk поля, например:
     *       Pages()->filter(...)->values(
     *               array(
     *                   'title', // обычное поле
     *                   'type' => array(    // поле, которое ссылается на fk поле
     *                           'fk_name' => 'type', // имя fk поля в другой модели
     *                           'values' => array('title', 'id') // список полей из fk поля
     *                       ),
     *               )
     *           )
     *
     *       Если $fields строка, метод вернет массив значений поля с указанным именем.
     *       Если $fields - массив, метод вернет массив массивов с перечисленными полями.
     *   $limit, $offset - аналогично параметрам метода limit()
     */
    function values($fields, $limit = null, $offset = null) {
        // Получим ссылку на backend
        $backend = NamiCore::getBackend();

        if (is_array($fields)) {
            $flat = false;
        } else {
            $flat = true;
            $fields = array($fields);
        }

        // т.к. в дальнейшем, для fk полей нужны идентификаторы, то по умолчанию их добавляем в выборку.
        // Если программист не укажет, что ему нужны в выдаче идентификаторы, то они просто удалятся из результатов
        $hide_ids = false;
        if (!in_array('id', $fields)) {
            $hide_ids = true;
        }

        // хранение результатов об fk полях
        $fk_field_data = array();
        $field_name = '';
        $fk_field_values = array();
        $fk_results = array();

        // определение наличия fk полей для выборки
        if (!$flat) {
            foreach ($fields as $fk_fld => $fk_values) {
                if (!is_array($fk_values))
                    continue;

                $fk_field_data[$fk_fld] = $fk_values;
                // удаление массива и превращение его ключа в обычное значение,
                // чтобы потом в это значение запихать значения fk полей ^_^
                unset($fields[$fk_fld]);
                $fields[] = $fk_fld;

                if ($hide_ids)
                    $fields[] = 'id';
            }

            if ($fk_field_data) {
                $field_name = key($fk_field_data);
                $fk_field_values = $fk_field_data[$field_name]['values'];
            }
        }
        unset($fk_fld);

        // Получим sql запроса
        $query = $this->getNamiQueryParts('raw_values', array('fields' => $fields, 'limit' => $limit, 'offset' => $offset));

        // Выполним запрос
        $backend->cursor->execute($query['sql'], $query['params']);

        // Получим результат
        $results = array();

        if ($flat) {
            foreach ($backend->cursor->fetchAll() as $row) {
                $results[] = $row[$query['fields'][0]->aliases[$fields[0]]];
            }
        } else {
            if ($fk_field_values) {
                $model_qs = NamiCore::getInstance()->getNamiModelMetadata($this->model);
            }
            foreach ($backend->cursor->fetchAll() as $row) {
                $result = array();
                foreach ($fields as $field) {
                    $result[$field] = $row[$query['fields'][0]->aliases[$field]];

                    // сбор данных для fk поля
                    if ($field == $field_name) {
                        $id = $row[$query['fields'][0]->aliases[$field]];

                        if ($fk_field_values) {
                            $fk_model_name = $model_qs->getField($field_name)->model;
                            $fk_model_qs = NamiQuerySet($fk_model_name);
                            $fk_results = $fk_model_qs->filter(array('id' => $id))->values($fk_field_values);

                            $result[$field] = $fk_results[0];
                        }
                    }
                }

                $results[] = $result;
            }
        }

        if (!$flat && $hide_ids) {
            foreach ($results as $key => $value) {
                if (isset($value['id']))
                    unset($results[$key]['id']);
            }
        }

        return $results;
    }

    /**
     *   Установить limit, который будут применены в методе all() или limit().
     *   Возвращает queryset.
     */
    function set_limit($limit) {
        $next = clone $this;
        $next->limit = $limit;
        return $next;
    }

    /**
     * Выборка указанного количества записей.
     * Это конечный метод, на котором работают остальные методы, выбирающие данные, такие как all() и count()
     * $limit - количество записей, null - не ограничено
     * $offset - смещение в наборе записей, null - не указывать смещение, 0
     */
    function limit($limit = null, $offset = null) {
        // Если установлен общий limit запроса (через set_limit), нужно его применить
        if ($this->limit) {
            $computed_limit = ($limit ? $limit : 0) + ($offset ? $offset : 0);

            if ($computed_limit) {
                if ($computed_limit > $this->limit) {
                    if ($offset) {
                        $limit = $offset < $this->limit ? $this->limit - $offset : 0;
                    } else {
                        $limit = $this->limit;
                    }
                }
            } else {
                $limit = $this->limit;
            }
        }

        // Получим ссылку на backend
        $backend = NamiCore::getBackend();

        // Получим sql запроса
        $query = $this->getNamiQueryParts('values', array('limit' => $limit, 'offset' => $offset));

        // Выполним запрос
        $backend->cursor->execute($query['sql'], $query['params']);

        // Пройдемся по результатам запроса, и сложим все в массив объектов
        $results = array();
        foreach ($backend->cursor->fetchAll() as $row) {
            // Создадим для каждой записи объект
            $record = null;

            // Идем по полям запроса
            foreach ($query['fields'] as $f) {
                // Собираем данные для наполнения очередного экземпляра модели
                $data = array();
                foreach ($f->aliases as $field => $alias) {
                    if (is_array($alias)) {
                        foreach ($alias as $language_name => $field_alias) {
                            $data[$field][$language_name] = $row[$field_alias];
                        }
                    } else {
                        $data[$field] = $row[$alias];
                    }
                }

                // Если модель еще не создана - создадим ее и инициализируем
                if (!$record) {
                    // Стандартная ситуация — объект основной модели
                    $record = new $this->model();
                    $record->setValuesFromDatabase($data);
                } else {
                    // Модель уже создана, отдадим поле ей для загрузки
                    $record->loadQueryEntity($f, $data);
                }
            }

            $results[] = $record;
        }
        return $results;
    }

    // Выборка всех записей
    function all() {
        # Просто limit без ограничений :D
        return $this->limit();
    }

    // Получение первой записи запроса
    function first() {
        $records = $this->limit(1, 0);
        return array_key_exists(0, $records) && $records[0] ? $records[0] : null;
    }

    /* Подсчет количества объектов */

    function count() {
        // Получим sql запроса
        $query = $this->getNamiQueryParts('count');

        // Получим ссылку на backend
        $backend = NamiCore::getBackend();

        // Выполним запрос
        $backend->cursor->execute($query['sql'], $query['params']);

        // Запросы типа count возвращают колонку count
        $row = $backend->cursor->fetchOne();

        /*  Если у нас был limit и в базе больше, чем limit записей,
          сделаем вид, что там их ровно limit :) */

        $count = $this->limit && $row['count'] > $this->limit ? $this->limit : $row['count'];

        return $count;
    }

    /**
      Получение кеш-ключа
     */
    protected function getCacheKey($params) {
        $key = array();
        foreach ($params as $k => $v)
            $key[] = "{$k}={$v}";
        return $this->model . ':' . join(',', $key) . ':' . NamiCore::getLanguage()->key;
    }

    /**
      Чтение из кеша
     */
    protected function getCached($params) {
        $key = $this->getCacheKey($params);
        return array_key_exists($key, self::$cache) ? unserialize(self::$cache[$key]) : false;
    }

    /**
      Кеширование объекта
     */
    protected function setCached($params, $obj) {
        $key = $this->getCacheKey($params);

        # Очищаем кеш, если добавляется новый элемент
        if (!array_key_exists($key, self::$cache) && count(self::$cache) + 1 >= NamiConfig::$queryset_get_cache_size) {
            array_shift(self::$cache);
        }

        self::$cache[$key] = serialize($obj);
        return true;
    }

    /**
      Получение одного объекта
     */
    function getNotCached($params) {
        return $this->get($params, false);
    }

    /**
      Получение одного объекта
     */
    function get($params, $use_cache = true) {
        // Проверим параметры, это должен быть массив или integer
        if (!is_array($params)) {
            if (!( is_numeric($params) || $params instanceof NamiModel ))
                throw new NamiException('Must use filter parameter, integer value or NamiModel instance to get model object ');
            $params = array('pk' => is_object($params) ? $params->meta->getPkValue() : (int) $params);
        }

        // Проверим кеш
        if ($use_cache && ( $obj = $this->getCached($params) ) !== false)
            return $obj;

        // TODO: перенести get в модель, а тут сделать get, использующий текущие параметры queryset'а
        // Создадим пустой NamiQuerySet
        $query_set = new NamiQuerySet($this->model);

        // Выбираем один объект
        $objects = $query_set->filter($params)->limit(1);

        $obj = array_key_exists(0, $objects) ? $objects[0] : null;

        // Закешируем объект
        $this->setCached($params, $obj);

        return $obj;
    }

    /**
     *   Получение объекта или выброс Http404
     *   Абсолютно аналогичен get(), кроме обработки пустого результата
     */
    function get_or_404($params, $use_cache = true) {
        $object = $this->get($params, $use_cache);
        if (!$object) {
            Builder::show404();
        }
        return $object;
    }

    /**
      Создание объекта по переданным аргументам
     */
    function create(array $params) {
        // Создадим новый объект с переданными параметрами
        $obj = new $this->model($params);

        // Сохраним его в БД и вернем
        $obj->save();

        return $obj;
    }

    /**
      Получение или создание объекта
     */
    function getOrCreate(array $params, $cached = true) {
        $obj = $this->get($params, $cached);

        if (!$obj) {
            $obj = $this->create($params);
        }

        return $obj;
    }

    /**
     *   Удаление записей, выбираемых текущим набором фильтров.
     */
    public function delete($limit = null, $offset = null) {
        // Получим ссылку на backend
        $backend = NamiCore::getBackend();

        // Получим sql запроса
        $query = $this->getNamiQueryParts('delete', array('limit' => $limit, 'offset' => $offset));

        // Выполним запрос
        $backend->cursor->execute($query['sql'], $query['params']);
    }

    /**
     *   Получение списка сущностей запроса для указанного составного поля.
     *
     */
    protected function getFieldQueryEntities($complex_field) {
        $core = NamiCore::getInstance();
        $entities = array();

        $seen_models = array(
            array(
                'model' => $this->model,
                'alias' => $core->mapper->getModelAlias($this->model),
            )
        );

        $path = array();
        $steps = explode('__', $complex_field);
        $null = false;

        while ($step = array_shift($steps)) {
            $sup_meta = $core->getNamiModelMetadata($seen_models[0]['model']);
            $field = $sup_meta->getField($step);

            if (!$field instanceof NamiFkDbField) {
                throw new NamiException("$step is not a NamiFkDbField");
            }

            if ($field->null) {
                $null = true;
            }

            $path[] = $step;
            $sup_field = $path[count($path) - 1];
            $sup_alias = $seen_models[0]['alias'];

            $alias = "{$sup_alias}__{$sup_field}";
            $model = $field->model;
            $meta = $core->getNamiModelMetadata($model);

            $entity = new NamiQueryEntity($model);
            $entity->path = $path;
            $entity->join = new NamiQueryJoin(
                $null ? 'LEFT' : 'INNER', $core->mapper->getModelTable($model), $alias, "{$sup_alias}.{$sup_field} = {$alias}.{$meta->pkname}"
            );

            // Вгружаем поля
            foreach ($meta->getFields() as $n => $v) {
                if (!$v instanceof NamiFkDbField) {
                    if ($v->localized) {
                        foreach (NamiCore::getAvailableLanguages() as $language) {
                            $falias = join('__', array_merge(array($this->model), $path, array($n))) . "__{$language->name}";
                            $entity->fields[] = "{$alias}." . $core->mapper->getFieldColumnName($v, $language) . " AS $falias";
                            $entity->aliases[$n][$language->name] = "$falias";
                        }
                    } else {
                        $falias = join('__', array_merge(array($this->model), $path, array($n)));
                        $entity->fields[] = "{$alias}." . $core->mapper->getFieldColumnName($v) . " AS $falias";
                        $entity->aliases[$n] = "$falias";
                    }
                }
            }

            array_unshift($seen_models, array('model' => $model, 'alias' => $alias));

            $entities[$model] = $entity;
        }

        return $entities;
    }

    public function resolveCheckField($name) {
        $meta = NamiCore::getInstance()->getNamiModelMetadata($this->model);
        $field = $meta->getField($name);

        return array(
            'alias' => NamiCore::getMapper()->getModelAlias($this->model),
            'fieldObject' => $field,
            'columnName' => NamiCore::getMapper()->getFieldColumnName($field),
            'fulltextColumnName' => NamiCore::getMapper()->getFieldFulltextColumnName($field),
        );
    }

    function distinct() {
        $next = clone $this;
        $next->distinct = true;
        return $next;
    }

}
