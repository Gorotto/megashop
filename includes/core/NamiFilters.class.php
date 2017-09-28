<?php

//Фильтратор.
//
//        $config = array(
//            'model' => 'CatalogEntry',
//            'view' => 'catalog/block_filters',
//            'filters' => array(
//                array('field' => 'price', 'title' => 'Цена'),
//                array('field' => 'city', 'title' => 'Город', 'values' => array('Красноярск', 'Дивногорск', 'Братск'), 'widget' => 'select'),
//                array('field' => 'area'),
//                array('field' => 'floor'),
//                array('field' => 'street_area', 'widget' => 'multiple'),
//            ),
//            'search' => array('title', 'category__title'),
//            'category' => $category,
//        );
//
//        $f = new NamiFilters($config);
//        $qs = $f->apply_to($qs);


class NamiFilters {

    private $extended_prefix = 'Catalog'; // на будущее. Вдруг у нас будет несколько расширенных каталогов
    // виджеты фильтров по-умолчанию
    private $classname_widget = array(
        'NamiBoolDbField' => 'checkbox',
        'NamiCharDbField' => 'select',
        'NamiDatetimeDbField' => 'range',
        'NamiEnumDbField' => 'select',
        'NamiFkDbField' => 'select',
        'NamiFloatDbField' => 'range',
        'NamiIntegerDbField' => 'range',
        'NamiPriceDbField' => 'range',
    );

    function __construct($config) {

        $this->model_name = $config['model'];
        $this->view = array_key_exists('view', $config) ? $config['view'] : null;
        $this->filters_config = array_key_exists('filters', $config) ? $config['filters'] : null;
        $this->search_config = array_key_exists('search', $config) ? $config['search'] : null;
        $this->category = array_key_exists('category', $config) ? $config['category'] : null;

        $this->model = new $this->model_name;
        $this->meta_vars = Meta::vars();
        $this->category_ids = $this->category ? array($this->category->id) : null;

        $this->filters = $this->filters_config ? $this->get_filters($this->filters_config) : array();
        // проверим, нужны ли здесь расширенные фильтры
        // без категории расширенные фильтры бесполезны
        if ($this->model instanceof $this->extended_prefix . 'Entry' && $this->category) {
            $this->filters = array_merge($this->filters, $this->get_filters_extended());
        }

        if ($this->search_config) {
            if (array_key_exists('search', $this->meta_vars) && $this->meta_vars['search']) {
                $value = trim(htmlspecialchars($this->meta_vars['search']));
            } else {
                $value = '';
            }
            $this->search = array(
                'fields' => $this->search_config,
                'value' => $value,
            );
        } else {
            $this->search = null;
        }
    }

    public function __get($name) {
        if ($name == 'query_string') {
            return $this->build_query_string();
        }
        return null;
    }

    /**
     *   Собираем информацию о вшитом фильтре на основе данных из конфига и описания модели   
     */
    private function get_filters($config) {
        $definition = $this->model->definition();

        // соберем, все, что нам известно о фильтре
        $filters = array();
        foreach ($config as $n => $filter) {
            $field = $filter['field'];
            $instance = $definition[$filter['field']];

            $p = array();
            // field
            $p['field'] = $field;

            // classname
            $classname = get_class($instance);
            $p['classname'] = $classname;

            // title
            if (array_key_exists('title', $filter)) {
                $title = $filter['title'];
            } elseif (array_key_exists('title', $instance->valueOptions)) {
                $title = $instance->valueOptions['title'];
            } else {
                $title = $field;
            }
            $p['title'] = $title;

            // model
            $p['model'] = array_key_exists('model', $instance->valueOptions) ? $instance->valueOptions['model'] : null;

            // widget
            if (array_key_exists('widget', $filter)) {
                $widget = $filter['widget'];
            } else {
                if (array_key_exists($classname, $this->classname_widget)) {
                    $widget = $this->classname_widget[$classname];
                } else {
                    $widget = 'select';
                }
            }
            $p['widget'] = $widget;

            // available values
            if (array_key_exists('values', $filter) && $filter['values']) {
                $available_values = $filter['values'];
            } else {
                $available_values = $this->get_available_values($field, $widget, $p['model']);
            }

            $p['available_values'] = $available_values;

            // value
            $p['value'] = $this->get_value($field, $widget, $available_values);

            // unit
            if (array_key_exists('unit', $filter)) {
                $unit = $filter['unit'];
            } else {
                $unit = '';
            }
            $p['unit'] = $unit;


            if ($available_values) {
                $filters[] = $p;
            }
        }
        return $filters;
    }

    /**
     *   Собираем данные о расширенном фильтре на основе настроек расширенного каталога для заданной категории
     */
    private function get_filters_extended() {
        $filters = array();

        $fields = NamiQuerySet($this->extended_prefix . 'FieldsetField')
            ->filter(array(
                'fieldset' => $this->category->fieldset,
            ))
            ->follow(1)
            ->sortedOrder()
            ->all();

        foreach ($fields as $n => $field) {
            if (!$field->filter_mode) {
                continue;
            }
            $p = array();
            $field_name = $field->field->name;

            // name
            $p['field'] = $field_name;

            // unit
            $p['unit'] = $field->field->unit;

            // title
            $p['title'] = $field->field->title;

            // widget
            if ($field->filter_mode == 'checkbox') {
                if ($field->field->field_type->storage_model == $this->extended_prefix . 'EntryBooleanValue') {
                    $widget = 'checkbox_single';
                } else {
//                    $widget = 'checkbox_multiple';
                    $widget = 'chosen';
                }
            } else {
                $widget = $field->filter_mode;
            }
            $p['widget'] = $widget;

            // available values
            if ($field->field->settings) {
                $available_values = array();
                foreach (explode("\n", $field->field->settings) as $value) {
                    $available_values[] = trim($value);
                }
            } else {
                $available_values = $this->get_available_values($field, $p['widget'], true);
            }
            $p['available_values'] = $available_values;

            // value
            $p['value'] = $this->get_value($field_name, $widget, $p['available_values']);
            if ($available_values && count($available_values) > 1) {
                $filters[] = $p;
            }
        }
        return $filters;
    }

    /**
     *   Достаем возможные значения фильтров
     */
    private function get_available_values($field, $widget, $model = null) {
        switch ($widget) {
            case 'select':
                return $this->get_distinct_values($field, $model);

            case 'checkbox_single':
                return array(0, 1);

            case 'hidden':
                return array(0, 1);

            case 'chosen':
                return $this->get_distinct_values($field, $model);

            case 'checkbox_multiple':
                return $this->get_distinct_values($field, $model);

            case 'yes_no':
                return array('yes', 'no', 'whatever');


            case 'range':
                if (is_object($field)) {
                    $field_name = $field->field->name;
                } else {
                    $field_name = $field;
                }


                $paths = Meta::getUriPathNames();
                $value = NamiQuerySet($this->model_name);

                // в пользовательской части фильтрация только по включенным элементам
                if ($paths[0] != 'cms') {
                    $value = $value->filter(array('enabled' => true));
                }

                if ($this->category_ids) {
                    $value = $value->filter(array('category__id__in' => $this->category_ids));
                }

                $has_null_values = $value->filter(array("{$field_name}__isnull" => true))->count();
                $min_value = $value->order($field_name)->first();
                $max_value = $value->orderDesc($field_name)->first();

                if (!$min_value || !$max_value) {
                    return array();
                }

                $max_value = ceil($max_value->$field_name);
                $min_value = floor($min_value->$field_name);

                if ($min_value > 0 && $has_null_values) {
                    $min_value = 0;
                }

                if ($max_value === $min_value) {
                    return array();
                }

                return array('min' => $min_value, 'max' => $max_value);
        }
        return array();
    }

    private function get_distinct_values($field, $model) {
        if (is_object($field)) {
            $field_name = $field->field->name;
            $prefix = strtolower($this->extended_prefix);
            $storage_model = strtolower($field->field->field_type->storage_model);
            // берем из базы все неповторяющиеся значения поля $i->field
            $cursor = NamiCore::getInstance()->getBackend()->getCursor();
            // вот из-за этого запроса в функцию приходится передевать $section и $category_ids
            $cursor->execute("select distinct value from (select entry, field, value from " . $storage_model . ") as v inner join " . $prefix . "entry e on e.id = v.entry inner join " . $prefix . "field f on f.id = v.field where e.category in (%s) and e.enabled = true and f.id = %s order by value", array($this->category_ids, $field->field->id));
            $result = $cursor->fetchAll();
            if (!$result) {
                return null;
            }
            $values = array();
            foreach ($result as $value) {
                $v = trim($value[0]);
                if ($v) {
                    $values[] = $v;
                }
            }
        } else {
            if ($model) {
                $values = NamiQuerySet($model)
                    ->values('id');
            } else {
                $result = NamiQuerySet($this->model_name)
                    ->filter(array(
                        "{$field}__isnotnull" => true,
                        "{$field}__ne" => '',
                    ))
                    ->order($field)
                    ->distinct()
                    ->values($field);
                $values = array();
                foreach ($result as $value) {
                    $v = trim($value);
                    if ($v) {
                        $values[] = $v;
                    }
                }
            }
        }
        return $values;
    }

    /**
     *   Достаем введенное пользователем значение
     */
    private function get_value($field, $widget, $available_values) {
        switch ($widget) {
            case 'select':
                if (!array_key_exists($field, $this->meta_vars) || !$this->meta_vars[$field]) {
                    return null;
                }
                $value = trim($this->meta_vars[$field]);
                if (!in_array($value, $available_values)) {
                    return null;
                }
                return $value;

            case 'checkbox_single':
                if (!array_key_exists($field, $this->meta_vars) || !$this->meta_vars[$field]) {
                    return false;
                }
                return true;

            case 'hidden':
                if (!array_key_exists($field, $this->meta_vars) || !$this->meta_vars[$field]) {
                    return false;
                }
                return true;

            case 'checkbox_multiple':
                if (!array_key_exists($field, $this->meta_vars) || !$this->meta_vars[$field] || !is_array($this->meta_vars[$field])) {
                    return array();
                }
                $values = array_intersect($available_values, $this->meta_vars[$field]);
                return $values;

            case 'chosen':
                if (!array_key_exists($field, $this->meta_vars) || !$this->meta_vars[$field] || !is_array($this->meta_vars[$field])) {
                    return array();
                }
                $values = array_intersect($available_values, $this->meta_vars[$field]);
                return $values;

            case 'yes_no':
                if (!array_key_exists($field, $this->meta_vars) || !$this->meta_vars[$field]) {
                    return null;
                }
                $value = trim($this->meta_vars[$field]);
                if (!in_array($value, $available_values)) {
                    return null;
                }
                return $value;

            case 'range':
                $values = array();
                if (array_key_exists("{$field}_min", $this->meta_vars)) {
                    $min_val = intval($this->meta_vars["{$field}_min"]);
                    if ($min_val && $min_val >= $available_values['min']) {
                        $values['min'] = $min_val;
                    }
                }

                if (array_key_exists("{$field}_max", $this->meta_vars)) {
                    $max_val = intval($this->meta_vars["{$field}_max"]);

                    if (
                        $this->meta_vars["{$field}_max"] != '' && ($max_val || $max_val === 0) && $max_val <= $available_values['max']
                    ) {

                        $values['max'] = $max_val;
                    }
                }
                return $values;
        }
        return null;
    }

    /**
     *   Непосредственно сама фильтрация queryset'а
     */
    public function apply_to($qs) {
        if (!array_key_exists('f', $this->meta_vars)) {
            return $qs;
        }

        foreach ($this->filters as $n => $filter) {
            $value = $filter['value'];
            $field_name = $filter['field'];
            if (!$value) {
                continue;
            }

            switch ($filter['widget']) {
                case 'select':
                    $fk_qs = null;
                    if (array_key_exists('model', $filter) && $filter['model']) {
                        $fk_qs = NamiQuerySet($filter['model']);
                    }
                    // в NestedSet в фильтр нужно включить всю ветку записи
                    // но не в админке
                    $paths = Meta::getUriPathNames();
                    if (($fk_qs instanceof NamiNestedSetQuerySet) && ($paths[0] != 'cms')) {
                        $children_ids = $fk_qs
                            ->filterChildren($value)
                            ->embrace($value)
                            ->values('id');
                        $qs = $qs->filter(array("{$field_name}__in" => $children_ids));
                    } else {
                        $qs = $qs->filter(array($field_name => $value));
                    }
                    break;

                case 'checkbox_single':
                    $qs = $qs->filter(array($field_name => $value));
                    break;

                case 'hidden':
                    $qs = $qs->filter(array($field_name => $value));
                    break;

                case 'checkbox_multiple':
                    $qs = $qs->filter(array("{$field_name}__in" => $value));
                    break;

                case 'chosen':
                    $qs = $qs->filter(array("{$field_name}__in" => $value));
                    break;

                case 'yes_no':
                    if ($value == 'whatever') {
                        break;
                    }
                    if ($value == 'yes') {
                        $filter_val = true;
                    } elseif ($value == 'no') {
                        $filter_val = false;
                    }
                    $qs = $qs->filter(array($field_name => $filter_val));
                    break;

                case 'range':
                    if (array_key_exists('min', $value)) {
                        $qs = $qs->filter(array("{$field_name}__ge" => $value['min']));
                    }
                    if (array_key_exists('max', $value)) {
                        $qs = $qs->filter(array("{$field_name}__le" => $value['max']));
                    }
                    break;
            }
        }

        // поиск
        if ($this->search && $this->search['value']) {
            $value = $this->search['value'];
            $q = null;
            foreach ($this->search['fields'] as $field) {
                if (!$q) {
                    $q = Q("{$field}__containswords", $value);
                } else {
                    $q = QOR(Q("{$field}__containswords", $value), $q);
                }
            }
            if ($q) {
                $qs = $qs->filter($q);
            }
        }

        return $qs;
    }

    /**
     *   Строит строку запроса для paginator'a например
     */
    private function build_query_string() {
        $values = array('f' => 1);
        foreach ($this->filters as $filter) {
            if (!$filter['value']) {
                continue;
            }
            if ($filter['widget'] == 'range') {
                if (array_key_exists('min', $filter['value'])) {
                    $values["{$filter['field']}_min"] = $filter['value']['min'];
                }
                if (array_key_exists('max', $filter['value'])) {
                    $values["{$filter['field']}_max"] = $filter['value']['max'];
                }
            } else {
                $values[$filter['field']] = $filter['value'];
            }
        }
        if ($this->search && $this->search['value']) {
            $values['search'] = $this->search['value'];
        }
        return http_build_query($values); /* php 5 only =) */
    }

    /**
     *   Рисуем фильтры
     */
    function __toString() {
        if (!$this->filters || !$this->view) {
            return '';
        }
        return (string) new View($this->view, array('filters' => $this->filters));
    }

}
