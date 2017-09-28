<?
class CatalogEntryQuerySet extends NamiQuerySet {
    protected $loadExtraFields = array();
    
    protected $field_map = array();
    
    /**
    *   Загрузить данные дополнительных полей.
    *   Принимает одно и более имя дополнительного поля для загрузки.
    *   Метод нужен для ускорения вывода списков позиций, если в списке требуется вывод
    *   значения дополнительного поля (первая фотка или цена, например).
    */
    function loadProperties($name) {
        $next = clone $this;
        if (is_array($name)) {
            $args = $name;
        } else {
            $args = func_get_args();
        }
        foreach ($args as $i) {
            if (! in_array($i, $next->loadExtraFields)) {
                $next->loadExtraFields[] = $i;
            }
        }
        return $next;
    }

    public function parseOrderField($field) {
        try {
            return parent::parseOrderField($field);
        } catch (NamiException $e) {
            return array('field' => "{$this->model}__{$field}__value");
        }
    }
    
    protected function _order(array $fields, $order) {
        foreach ($fields as $name) {
            $possible_extra_field =  mb_strpos($name, '__', 0, 'utf-8') === false
                                  && ! NamiCore::getInstance()->getNamiModelMetadata($this->model)->fieldExists($name)
                                  && ! in_array($name, $this->loadExtraFields);
            if ($possible_extra_field) {
                $this->loadExtraFields[] = $name;
            } 
        }
        return parent::_order($fields, $order);
    }
    
    protected function query($params, $embrace = false) {
        if (is_array($params)) {
            foreach (array_keys($params) as $field_op) {
                if (preg_match('/^([^0-9][a-z_0-9]+?)(?:__[a-z]+)?$/ui', $field_op, $matches)) {
                    $name = $matches[1];
                    $possible_extra_field =  mb_strpos($name, '__', 0, 'utf-8') === false
                                          && ! NamiCore::getInstance()->getNamiModelMetadata($this->model)->fieldExists($name)
                                          && ! in_array($name, $this->loadExtraFields);
                    if ($possible_extra_field) {
                        $this->loadExtraFields[] = $name;
                    } 
                }
            
            }
        }

        return parent::query($params, $embrace);
    }


    public function resolveCheckField($name) {
        try {
            return parent::resolveCheckField($name);
        } catch (NamiException $e) {
            if (array_key_exists($name, $this->field_map)) {
                $model = $this->field_map[$name];

                $meta = NamiCore::getInstance()->getNamiModelMetadata($model);
                $field = $meta->getField('value');

                return array(
                    'alias'                 => NamiCore::getMapper()->getModelAlias($this->model) . "__$name",
                    'fieldObject'           => $field,
                    'columnName'            => NamiCore::getMapper()->getFieldColumnName($field),
                    'fulltextColumnName'    => NamiCore::getMapper()->getFieldFulltextColumnName($field),
                );
            } else {
                throw $e;
            }
        } 
    }


    /**
    *   Получение сущностей запроса - выполняется при генерации SQL-запроса.
    *   Добавлеяет дополнительные сущности для загрузки списка полей, активированных через loadProperties.
    */
    protected function getNamiQueryEntities($model = null, $depth = 0, $path = array(), $supalias = null, $null = false) {
        $entities = parent::getNamiQueryEntities($model, $depth, $path, $supalias, $null);

        if (is_null($model) && count($this->loadExtraFields)) {
            /*  Для того, чтобы загрузка полей работала, в запросе обязательно
                должны быть выбраны категории и наборы полей категорий.
                Так что пройдемся по сущностям запроса и проверим чо как. */
            $required_entities = $this->getFieldQueryEntities('category__fieldset');
            foreach ($entities as $entity) {
                if (array_key_exists($entity->model, $required_entities)) {
                    unset($required_entities[$entity->model]);
                }
                if (! count($required_entities)) {
                    break;
                }
            }
            foreach ($required_entities as $entity) {
                $entities[] = $entity;
            }

            /*  Первым делом получим список вхождений нужных нам полей.
                В каталоге могут иметься поля с одинаковым именем, но разные по своей сути.
                Такие поля не могут одновременно входит в один и тот же набор полей,
                но вполне могут присутствовать в разных наборах. Например, поля «Производитель самолетов» и
                «Автопроизводитель», оба имеют имя manuf. Соответственно при поиске по всему каталогу
                с условием manuf__icontains => 'mitsubishi' должны проверяться и производители самолетов
                и автопроизводители.
                Поэтому пройдемся по вхождениям и объеденим в группы по имени поля.
                Каждая такая группа будет действовать как отдельная сущность запроса.

                Помимо построения списка вхождения, мы можем закешировать для модели CatalogEntry
                информацию о том, в каких наборах есть выбранные поля, а в каких нет. Это важно,
                так как выбранные дополнительные поля скорее всего будут выводиться в списке, и если
                информацию о наличии полей не закешировать, будут дополнительные запросы к базе.
                Кешировать нужно не только наличие поля, но и его отсутствие. */
            $property_entries = array();
            $prop_names = $this->loadExtraFields;
            
            $fieldsets = array();

            foreach (CatalogFieldsetFields(array('field__name__in' => $prop_names))->follow(2)->all() as $link) {
                $name = $link->field->name;
                if (! array_key_exists($name, $property_entries)) {
                    $property_entries[$name] = array($link);
                } else {
                    $property_entries[$name][] = $link;
                }
                
                if (! array_key_exists($link->fieldset->id, $fieldsets)) {
                    $fieldsets[$link->fieldset->id] = array();
                }
                $fieldsets[$link->fieldset->id][$name] = $link->field;
            }
            
            // Пройдемся по зачатку кеша полей и отметим отсутствующие поля
            foreach ($prop_names as $name) {
                foreach ($fieldsets as $id => $fields) {
                    if (! array_key_exists($name, $fields)) {
                        $fieldsets[$id][$name] = false;
                    }
                }
            }
            
            // Закешируем в модели выбранные из базы данных вхождения, чтобы не делать потом лишних запросов при выводе списка
            CatalogEntry::cacheFieldLinks($fieldsets);
            
            /*  Теперь в $property_entries по имени поля лежат массивы вхождений.
                Ура, можно генерировать сущности запроса :3 */
            foreach ($property_entries as $prop_name => $entries) {
                /*  Для каждого поля, которое мы выбираем, придется сгенерировать отдельную псевдотаблицу,
                    каждая такая псевдотаблица будет представлена объектом NamiQueryEntity.
                    Воспользуемся фичей `select from (select *) as alias`, почитать про нее
                    можно тут http://dev.mysql.com/doc/refman/5.0/en/from-clause-subqueries.html
                    Запрос на выборку поля manuf будет выглядеть примерно так:
                        select en.id, en.title, ca.title as ca, fs.title as fs, manuftable.value as manuf
                        from `catalogentry` as en
                        inner join `catalogcategory` ca
                            on ca.id = en.`category`
                        inner join `catalogfieldset` fs
                            on fs.id = ca.`fieldset`
                        left join (select id, field, entry, value from catalogentrystringvalue
                                   where field in (select id from catalogfield where name='manuf') ) as manuftable
                            on manuftable.field in (select field from catalogfieldsetfield where fieldset = fs.id)
                               and en.id = manuftable.entry
                    Информацию о том, какую таблицу catalogentry*value использовать при выборке
                    возьмем из типа поля. Если таблиц оказалось несколько - используем union. */

                $core = NamiCore::getInstance();
                
                $entity = new NamiQueryEntity();
                $entity->path[] = $prop_name;

                $alias = sprintf("%s__%s", $core->mapper->getModelAlias($this->model), $prop_name);
                
                // Соберем для каждой отдельной модели списки идентификаторов полей
                $field_models = array();
                foreach ($entries as $entry) {
                    $model = $entry->field->field_type->storage_model;
                    if (! array_key_exists($model, $field_models)) {
                        $field_models[$model] = array($entry->field->id);
                    } else {
                        $field_models[$model][] = $entry->field->id;
                    }
                }
                
                // Закешируем каким полям соответствует какая модель
                $this->field_map[$prop_name] = $entries[0]->field->field_type->storage_model;
                
                // Пройдемся по собранным моделям и сгенерируем список запросов
                $queries = array();
                $table_query = 'select id, field, entry, value from %s where field in (%s)';
                foreach ($field_models as $model => $fields) {
                    $queries[] = sprintf($table_query,
                        $core->mapper->getModelTable($model),
                        join(',', $fields)
                    );
                }
                
                // Наконец-то можно намутить join
                $entity->join = new NamiQueryJoin(
                    "LEFT",
                    "(" . join(' union ', $queries) . ")",
                    $alias,
                    "{$alias}.field in (select field from catalogfieldsetfield where fieldset = _catalogentry__category__fieldset.id) " .
                    " and _catalogentry.id = {$alias}.entry"
                );
                
                // Заполним поля - нам нужны все
                foreach (array('id', 'field', 'entry', 'value') as $field_name) {
                    $field_alias = join('__', array($this->model, $prop_name, $field_name));
                    $entity->fields[] = "{$alias}.{$field_name} AS {$field_alias}";
                    $entity->aliases[$field_name] = $field_alias;
                }

                $entities[] = $entity;

            }
        }
        
        return $entities;
    }
}