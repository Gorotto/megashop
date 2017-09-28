<?

/**
  Маппер базы данных для MySQL
 */
class NamiMysqlDbMapper extends NamiDbMapper {

    /**
     * 	Описание charset и collation для каждой локали. Ключ - локаль, значение — SQL-код
     * 	Пример:
     * 	'ru_RU.UTF-8' => 'CHARSET UTF8 COLLATE utf8_general_ci
     * 	При отсутствии ключа берется значение 'DEFAULT'
     */
    protected $collations = array(
        'DEFAULT' => 'CHARSET UTF8 COLLATE utf8_general_ci',
    );
    // Описание типов данных колонок этого бэкэнда
    protected $creation_data_types = array
        (
        'NamiAutoDbField' => 'INTEGER UNSIGNED AUTO_INCREMENT',
        'NamiIntegerDbField' => 'INTEGER',
        'NamiFloatDbField' => 'FLOAT',
        'NamiBoolDbField' => 'TINYINT UNSIGNED',
        'NamiCharDbField' => 'VARCHAR(%{maxlength})',
        'NamiTextDbField' => 'LONGTEXT',
        'NamiImageDbField' => 'TEXT',
        'NamiFkDbField' => 'INTEGER UNSIGNED',
        'NamiDatetimeDbField' => 'DATETIME',
        'NamiEnumDbField' => 'ENUM(%{values})',
        'NamiFileDbField' => 'TEXT',
        'NamiFlvDbField' => 'TEXT',
        'NamiNewFileDbField' => 'TEXT',
        'NamiPriceDbField' => 'DECIMAL(10,2)',
        'NamiArrayDbField' => 'TEXT',
    );

    /**
      Описание маппирования операторов выборки
      %s - значение, которое будет подставлено в запрос
     */
    protected $operator_mapping = array(
        'ne' => '%{field} != %s',
        'eq' => '%{field} = %s',
        'exact' => '%{field} = %s',
        'iexact' => '%{field} LIKE %s',
        'contains' => '%{field} LIKE BINARY %s',
        'icontains' => '%{field} LIKE %s',
        'gt' => '%{field} > %s',
        'ge' => '%{field} >= %s',
        'lt' => '%{field} < %s',
        'le' => '%{field} <= %s',
        'startswith' => '%{field} LIKE BINARY %s',
        'endswith' => '%{field} LIKE BINARY %s',
        'istartswith' => '%{field} LIKE %s',
        'iendswith' => '%{field} LIKE %s',
        'containswords' => '%{field} LIKE %s',
        'isnull' => '%{field} IS NULL',
        'notin' => "%{field} not in ( %s )",
        'isnotnull' => '%{field} IS NOT NULL',
        'substringof' => "%s LIKE BINARY CONCAT( %{field}, '%' )",
        'in' => "%{field} in ( %s )",
        'match' => 'MATCH( %{field} ) AGAINST ( %{%{fts_param}} IN BOOLEAN MODE )',
    );

    /**
      Получение списка таблиц
     */
    function getTableList(NamiDbCursor $cursor) {
        $cursor->execute("SHOW TABLES");

        $tables = array();
        foreach ($cursor->fetchAll() as $row) {
            $tables[] = $row[0];
        }

        return $tables;
    }

    /**
      Получение списка колонок модели, имеющихся в базе данных
     */
    function getColumnList(NamiDbCursor $cursor, $model) {
        $table = $this->getModelTable($model);

        $cursor->execute("SHOW COLUMNS FROM `{$table}`");

        $columns = array();
        foreach ($cursor->fetchAll() as $row) {
            $columns[] = $row[0];
        }

        return $columns;
    }

    /**
      Получение списка индексов, для каждого индекса возвращаются флаги primary и unique
     */
    function getIndexList(NamiDbCursor $cursor, $model) {
        $table = $this->getModelTable($model);

        $cursor->execute("SHOW INDEX FROM `{$table}`");

        $indexes = array();
        while ($row = $cursor->fetchOne()) {
            $indexes[$row[2]] = array
                (
                'primary_key' => ( $row[2] == 'PRIMARY' ),
                'unique' => (!(boolean) $row[1] ),
            );
        }

        return $indexes;
    }

    /**
      Получение строки создания индекса
     */
    function getIndexCreationSql(NamiIndex $idx) {
        $table = $this->getModelTable($idx->model);

        if ($idx->primary) {
            return "ALTER TABLE `{$table}` ADD PRIMARY KEY (" . implode($idx->fields, ', ') . ")";
        } else {
            $names = array();
            foreach ($idx->fields as $name) {
                $names[] = "`{$name}`";
            }
            return 'CREATE' . ( $idx->unique ? ' UNIQUE' : '' ) . ( $idx->fulltext ? ' FULLTEXT' : '' ) . ' INDEX `' .
                $idx->getName() . "` ON `{$table}` (" . join(', ', $names) . ")";
        }
    }

    /**
      Получение строки удаления индекса
     */
    function getIndexRemovingSql($model, $index_name) {
        $table = $this->getModelTable($model);
        return "DROP INDEX `{$index_name}` ON `{$table}`";
    }

    /**
      Получение строки создания колонки модели
     */
    function getColumnCreationSql($model, $column, $language = null) {
        // Получим метаданные модели для исследования
        $meta = NamiCore::getInstance()->getNamiModelMetadata($model);
        // Получим ссылку на поле
        $field = $meta->getField($column);

        // Получим строку создания колонки
        $cs = $this->getColumnDefinitionSql($field, $language);

        if (!is_null($cs)) {
            // Сгенерим имя таблицы
            $table = $this->getModelTable($model);
            // Возвращаем то, что получилось
            return "ALTER TABLE `{$table}` ADD COLUMN `" . $this->getFieldColumnName($field, $language) . "` $cs " . ( $field->null ? 'NULL' : 'NOT NULL' );
        } else {
            return NULL;
        }
    }

    /**
      Получение имени таблицы модели
     */
    function getModelTable($model) {
        return ( NamiConfig::$db_prefix ? NamiConfig::$db_prefix : '' ) . strtolower($model);
    }

    /**
      Получение alias-а таблицы модели для выполнения запросов
     */
    function getModelAlias($model) {
        // Желательно, чтобы alias не пересекался с таблицами, поэтому просто добавим _
        return '_' . strtolower($model);
    }

    /**
      Маппирование оператора NamiQC
     */
    function getOperatorSql($field, $op, $extra = array()) {
        if (!array_key_exists($op, $this->operator_mapping)) {
            throw new NamiException("Unknown operator '{$op}");
        }

        $data = array_merge(array('field' => $field), $extra);

        return NamiUtilities::array_printf($this->operator_mapping[$op], $data);
    }

    /**
      Проверка существования оператора
     */
    function operatorExists($op) {
        return array_key_exists($op, $this->operator_mapping);
    }

    /**
      Получение SQL-строки limit offset
     */
    function getLimitOffsetSql($limit = null, $offset = null) {
        # Проверим аргументы
        if (is_null($limit)) {
            if (is_null($offset))
                return '';

            throw new NamiException('Cannot use offset without a limit');
        }

        if (!is_numeric($limit))
            throw new NamiException('Must use a numeric limit');

        if (!is_null($offset) && !is_numeric($offset))
            throw new NamiException('Must use a numeric offset');

        return "LIMIT " . ( $offset ? "{$offset}," : '' ) . $limit;
    }

    function getColumnDefinitionSql(NamiDbField $field, $language = null) {
        $sql = parent::getColumnDefinitionSql($field, $language);
        if ($language) {
            // Для текстовых полей указываем charset и collate :3
            if ($field instanceof NamiCharDbField || $field instanceof NamiTextDbField || $field instanceof NamiEnumDbField) {
                $collation = array_key_exists($language->name, $this->collations) ? $this->collations[$language->name] : $this->collations['DEFAULT'];
                $sql .= " {$collation}";
            }
        }
        return $sql;
    }

    /**
     * 	Получение списка колонок, которые нужно создать для этого поля
     * 	Возвращает массив колонок
     */
    function getFieldColumns($field) {
        $columns = array();
        if ($field->localized) {
            foreach (NamiCore::getAvailableLanguages() as $language) {
                $columns[] = new NamiDbColumn($this->getFieldColumnName($field, $language), $language);
                if ($field->fulltext) {
                    $columns[] = new NamiDbColumn($this->getFieldFulltextColumnName($field, $language), $language);
                }
            }
        } else {
            $columns[] = new NamiDbColumn($this->getFieldColumnName($field));
            if ($field->fulltext) {
                $columns[] = new NamiDbColumn($this->getFieldFulltextColumnName($field));
            }
        }
        return $columns;
    }

    /**
     * 	Получение SQL создания переданного поля в указанной модели.
     * 	Возвращает массив, ключи — имена колонок таблицы, которые нужно создать, значения — соответствующие SQL-запросы.
     * 	Если создавать ничего не требуется, возвращает пустой массив.
     */
    function getFieldCreationSql($model, NamiDbField $field) {
        $queries = array();

        foreach ($this->getFieldColumns($field) as $column) {
            $definition = $this->getColumnDefinitionSql($field, $column->language);
            if ($definition) {
                $table = $this->getModelTable($model);
                $queries[$column->name] = "ALTER TABLE `{$table}` ADD COLUMN `{$column->name}` {$definition} " . ( $field->null ? 'NULL' : 'NOT NULL' );
            }
        }

        return $queries;
    }

    /**
     *   Преобразование списка слов для полнотекстового поиска
     *   MySQL не индексирует слова короче 4 букв, так что добиваем их удобным символом до нужно длины.
     */
    function fulltext_prepare_words($words) {
        $min_len = 4;
        foreach ($words as $k => $v) {
            $len = mb_strlen($v, 'utf-8');
            if ($len < $min_len) {
                $words[$k] = str_repeat('_', $min_len - $len) . $v;
            }
        }

        return $words;
    }

}
