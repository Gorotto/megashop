<?

/**
  Оператор - проверка значения переменной
 */
class NamiQCCheckOp extends NamiQCOp {

    static private $ftsParamIdx = 1;
    protected $op;
    protected $field;
    protected $value;

    static private function getFtsParamName() {
        return "fts" . self::$ftsParamIdx++;
    }

    /**
      Конструктор
     */
    function __construct($field, $value) {
        // Разделим field на имя переменной и оператор
        $pieces = explode('__', $field);

        // Проверим оператор поиска, если он не задан - используем exact
        if (count($pieces) < 2 || !NamiCore::getMapper()->operatorExists($pieces[count($pieces) - 1])) {
            $this->op = 'exact';
        } else {
            $this->op = array_pop($pieces);
        }

        // Сгенерим имя поля (оно могло быть составным, и из него мог быть изъят оператор, так что выполняем join)
        $this->field = join('__', $pieces);

        // Значение в любом случае — то, что передали. Приведение экземпляров моделей к значениям ключевого поля будет сделано прямо перед выборкой
        $this->value = $value;
    }

    /*  Получение частей SQL-кода запроса, соответствующего данному условию выборки
      Аргументы:
      $model - имя модели, для которой выполняется запрос
      Возвращает
      array( 'joins'=>array(), where=>'sql', params=>array(...))
      joins  - array( array( 'alias'=>'mytable', 'table'=>'mytable', 'condition'=>'', 'type'=>'inner join' ) ... )
      where  - SQL-код с позиционными плейсхолдерами вида %s
      params - array параметров для where
     */

    function getNamiQueryParts($model, $queryset = null) {
        // То, что будем возвращать
        $result = array
            (
            'joins' => array(),
            'where' => '',
            'params' => array(),
            'expressions' => array(),
        );

        // Поле, которое преобразуем в SQL
        $field = $this->field;

        // Класс поля
        $field_class = null;

        // Экстра-данные поля
        $extra = array();


        // Поле * — все полнотекстовые поля модели
        if ($field == '*') {
            if ($this->op !== 'match') {
                throw new NamiException("Pseudo-field '*' could not be used in '{$this->op}' operator. Consider 'match'.");
            }

            // Создадим экземпляр модели
            $meta = NamiCore::getInstance()->getNamiModelMetadata($model);
            // Получим alias таблицы
            $alias = NamiCore::getMapper()->getModelAlias($model);

            $fields = array();
            $fts = array();
            foreach ($meta->getFields() as $f) {
                if ($f->fulltext) {
                    $fields[] = "{$alias}." . NamiCore::getMapper()->getFieldColumnName($f);
                    $fields[] = "{$alias}." . NamiCore::getMapper()->getFieldFulltextColumnName($f);
                    $fts[] = "{$alias}." . NamiCore::getMapper()->getFieldFulltextColumnName($f);
                }
            }

            if (!$fields) {
                throw new NamiException("Could not perform '*__match' on '{$model}'. There are no fulltext columns.");
            }

            $field = join(', ', $fields);
            $extra['fts'] = join(', ', $fts);
            $field_class = 'NamiTextDbField';
        } else if (strpos($field, '__') === false) {
            // Проверяем поле. Самый простой вариант — в поле нет двойных подчерков, это просто поле текущей модели
            // Создадим экземпляр модели
            $meta = NamiCore::getInstance()->getNamiModelMetadata($model);

            // Первая проверка - замена primary key
            if ($field == 'pk')
                $field = $meta->pkname;

            // Попросим QuerySet разрезолвить для нас поле основной модели
            $info = $queryset->resolveCheckField($field);

            if ($info) {
                $fieldObject = $info['fieldObject'];
                $field_class = get_class($fieldObject);

                // Сгенерируем имя поля
                $field = "{$info['alias']}.{$info['columnName']}";

                // Дополнительная инфа по fulltext-полям
                if ($this->op == 'match' && $fieldObject->fulltext) {
                    $field .= ", {$info['alias']}.{$info['fulltextColumnName']}";
                    $extra['fts'] = "{$info['alias']}.{$info['fulltextColumnName']}";
                }
            } else {
                throw new NamiException("Field '{$field}' not found in model {$model}");
            }

            /*
              // Проверим наличие поля в модели
              if( $meta->fieldExists( $field ) ) {
              // Получим alias таблицы
              $alias = NamiCore::getMapper()->getModelAlias( $model );

              $fieldObject = $meta->getField( $field );
              $field_class = get_class( $fieldObject );

              // Сгенерируем имя поля
              $field = "{$alias}.". NamiCore::getMapper()->getFieldColumnName( $fieldObject );

              // Дополнительная инфа по fulltext-полям
              if( $this->op == 'match' && $fieldObject->fulltext ) {
              $field .= ", {$alias}.". NamiCore::getMapper()->getFieldFulltextColumnName( $fieldObject );
              $extra['fts'] = "{$alias}.". NamiCore::getMapper()->getFieldFulltextColumnName( $fieldObject );
              }
              } else {
              throw new NamiException( "Field '{$field}' not found in model {$model}" );
              }
             */
        } else {
            // complex field lookup, связка по ForeignKey
            try {
                $cf = new NamiComplexField($model, $field);
            } catch (NamiException $e) {
                if ($e->getCode() == 1) {
                    throw new NamiException("Incorrect QC check operator '{$field}' for {$model} model");
                } else {
                    throw $e;
                }
            }

            // Имя поля
            $field = $cf->field;

            // Класс поля
            $field_class = $cf->field_class;

            // Дополнительные данные
            if ($cf->extra) {
                $extra = array_merge($extra, $cf->extra);
            }

            // Добавим join-ов
            $result['joins'] = $cf->joins;
        }

        $value = null;

        // Значение, которое подставляем
        if (is_array($this->value)) {
            $value = $this->value;

            foreach ($value as $k => & $v) {
                $v = call_user_func(array($field_class, 'getCheckOpValue'), $v);
            }
        } else {
            $value = call_user_func(array($field_class, 'getCheckOpValue'), $this->value);
        }

        // Если передан instance модели - преобразуем его в значение PK
        if ($value instanceof NamiModel) {
            $value = $value->meta->getPkValue();
        }

        // Запиздачим value, если это требуется оператору
        switch ($this->op) {
            case 'iexact':
                $value = $this->getLikeValue($value, true, true);
                break;
            case 'contains':
            case 'icontains':
                $value = $this->getLikeValue($value, false, false);
                break;
            case 'startswith':
            case 'istartswith':
                $value = $this->getLikeValue($value, true, false);
                break;
            case 'notin':
                if (count($value) == 0) {
                    $value[] = 0;   // нельзя делать null, потому что он ничего не сматчит
                }
                break;
            case 'in':
                if (count($value) == 0) {
                    $value[] = null;
                }
                break;
            case 'containswords':
                $value = $this->getLikeValue($value, false, false, true);
                break;
            case 'endswith':
            case 'iendswith':
                $value = $this->getLikeValue($value, false, true);
                break;
            case 'match':
                if (!array_key_exists('fts', $extra)) {
                    throw new NamiException("Can't use 'match' operator on '{$this->field}' field because it has fulltext = false");
                }
                $fulltextProcessor = new NamiFulltextProcessor();

                $words = array();

                foreach ($fulltextProcessor->get_all_forms($value) as $word_forms) {
                    $word_forms = NamiCore::getMapper()->fulltext_prepare_words($word_forms);
                    $words[] = '(' . join(' ', $word_forms) . ')';
                }

                $value = '>(' . ( $value ) . ') <+(' . join(' ', $words) . ')';

                $extra['fts_param'] = self::getFtsParamName();

                break;
        }

        // Получим маппинг оператора
        if (is_null($value)) {
            if ($this->op == 'eq' || $this->op == 'exact' || $this->op == 'iexact')
                $this->op = 'isnull';
            elseif ($this->op == 'ne')
                $this->op = 'isnotnull';
        }

        $operator = NamiCore::getMapper()->getOperatorSql($field, $this->op, $extra);

        if (!$operator) {
            throw new NamiException("Unknown NamiQueryCheck operator '{$this->op}'");
        }

        if ($this->op == 'match') {
            $result['expressions']['fulltext_relevance'] = $operator;
            $result['params'][$extra['fts_param']] = $value;
        } else {
            if (is_null($value)) {
                $operator = NamiUtilities::array_printf($operator, array('NULL'));
            } else {
                $result['params'][] = $value;
            }
        }

        // Наконец сгенерируем условие выборки
        $result['where'] = $operator;

        return $result;
    }

}
