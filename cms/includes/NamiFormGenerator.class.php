<?php

/**
 *
 * Класс создания формы для модели Nami
 *
 * Использование:
 * В модели нужно создать дополнительный параметр description, который описывает поля модели
 * (если этого не сделать, будет выведен прототип формы на основе definition)
 *
 * static public $description = array(
 * 'title'     => array('title' => 'Название', 'widget' => 'string'),
 * 'name'      => array('title' => 'Название по-английски'),
 * );
 *
 * По умолчания в качестве title используется name,
 * в качестве widget'а используется widget по умолчанию для этого типа поля.
 *
 * title - заголовок
 * widget - checkbox, date, file, image, images, price, richtext, select, string, text
 * choices (для select'a) - предполагает получить, либо массив значений, либо название модели,
 * из которой эти значения можно достать (в ней обязательно должны быть поля id, title и enabled) - ДОРАБОТАТЬ,
 * либо название атрибута модели с массивом значений
 *
 * Использование в шаблоне админки:
 *
 * print NamiFormGenerator::forModel('GalleryImage');
 */
class NamiFormGenerator
{

    //TODO нужно переписать это трололо.
    //1. виджет должен принимать любые папрметры переданные ему из описания модели
    //2. виджеты должны иметь отдельные блоки:
    // - html блок элементов управления
    // - блок вспомогательных элементов, котокрые не будут дублироваться в каждой форме (карта для яндекса например)
    // - блок инициализации js данных
    //3. нужно иметь возможно через браузер включать режим построения формы на основе
    //модели и на основе ее описания. Например через GET параметр
    //4. плагин

    public static function forModel($modelName)
    {
        $model = new $modelName;
        $views = array();

        $fieldtype_widget = array(
            'NamiTextDbField' => 'text',
            'NamiCharDbField' => 'string',
            'NamiIntegerDbField' => 'string',
            'NamiFloatDbField' => 'string',
            'NamiEnumDbField' => 'string',
            'NamiBoolDbField' => 'checkbox',
            'NamiDatetimeDbField' => 'date',
            'NamiImageDbField' => 'image',
            'NamiFileDbField' => 'file',
        );

        $definition = $model->definition();

        ob_start();

        if (!property_exists($model, 'description')) {
            // у модели нет свойства description - рисуем прототип формы на основе definition
            // title достаем из name, widget подбираем на основе типа поля
            foreach ($definition as $name => $def) {
                $widget = 'string';
                $classname = get_class($def);
                if (array_key_exists($classname, $fieldtype_widget)) {
                    $widget = $fieldtype_widget[$classname];
                }
                print new View("core/widgets/{$widget}", array('name' => $name, 'title' => $name, 'choices' => null, 'info' => null));
            }
        } else {

            $is_develop_mode = CmsApplication::is_develop_mode();
            // у модели есть свойство description - делаем все красиво
            foreach ($model->description as $name => $params) {

                if (!$is_develop_mode && array_key_exists("develop_only", $params)) {
                    continue;
                }

                // определяем widget
                $widget = 'string'; // по умолчанию
                if (array_key_exists('widget', $params) && $params['widget']) {
                    // если пришел параметром - берем его
                    $widget = $params['widget'];
                } else {
                    // если не пришел параметром, пробуем найти виджет для этого типа поля
                    if (array_key_exists($name, $definition)) {
                        $classname = get_class($definition[$name]);
                        if (array_key_exists($classname, $fieldtype_widget)) {
                            $widget = $fieldtype_widget[$classname];
                        }
                    }
                }

                // определяем title
                if (array_key_exists('title', $params) && $params['title']) {
                    $title = $params['title'];
                } else {
                    $title = $name;
                }

                // choices
                $choices = array();
                if (array_key_exists('choices', $params) && $params['choices']) {
                    if (is_array($params['choices'])) {
                        foreach ($params['choices'] as $n => $i) {
                            $choices[] = array('id' => $n, 'title' => $i);
                        }
                    } elseif (property_exists($model, $params['choices'])) {
                        foreach ($model->{$params['choices']} as $n => $i) {
                            $choices[] = array('id' => $n, 'title' => $i);
                        }
                    } else {
                        // здесь надо проверить, есть ли такая моделька
                        $choices_db = NamiQuerySet($params['choices']);

                        //Для случаев использования мультиязычных сайтов
                        //нужно переписать на ->only()->all()
                        if ($choices_db instanceof NamiSortableQuerySet) {
                            $choices_db = $choices_db
                                ->sortedOrder()
                                ->values(array('id', 'title'));
                        } elseif ($choices_db instanceof NamiNestedSetQuerySet) {
                            $choices_db = $choices_db
                                ->treeOrder()
                                ->values(array('id', 'title', 'lvl'));
                        } else {
                            $choices_db = $choices_db
                                ->order("title")
                                ->values(array('id', 'title'));
                        }

                        foreach ($choices_db as $e) {
                            $choices[] = $e;
                        }
                    }
                }

                $info = null;
                if (array_key_exists('info', $params)) {
                    $info = $params['info'];
                }

                $widget_data = array('name' => $name, 'title' => $title, 'choices' => $choices, 'info' => $info);

                if (array_key_exists('not_use_own_id', $params) && $params['not_use_own_id']) {
                    $widget_data['not_use_own_id'] = $params['not_use_own_id'];
                }


                if ($widget == "form_control") {
                    $rules = "";

                    foreach ($params['values'] as $key => $value) {
                        if (isset($value['hide'])) {
                            $hide_rule = " data-hide_" . $key . "='" . implode(",", $value['hide']) . "'";
                        }

                        if (isset($value['show'])) {
                            $show_rule = " data-show_" . $key . "='" . implode(",", $value['show']) . "'";
                        }

                        $rules .= $hide_rule . $show_rule;
                    }

                    $rules .= " ";

                    $widget_data['values'] = $params['values'];
                    $widget_data['rules'] = $rules;
                }


                if (property_exists($modelName, "field_json_schema")) {
                    if (array_key_exists($widget_data['name'], $modelName::$field_json_schema)) {
                        $widget_data['json_schema'] = $modelName::$field_json_schema[$widget_data['name']];
                    }
                }


                $view = new View("core/widgets/{$widget}", $widget_data);

                if (isset($params['depend_of']) && $params['depend_of']) {
                    $views['dependent'][$name] = array('depend_of' => $params['depend_of'], 'view' => $view);
                } else {
                    $views['regular'][$name] = array('view' => $view);
                }
            }

            $k = 0;
            foreach ($model->description as $name => $params) {
                $k++;
                if (isset($views['regular'][$name])) {
                    $line = $views['regular'][$name];

                    echo $line['view'];

                    // чтобы соблюсти очередь и сгенерировать div вокруг зависимых элементов, нужно протись циклом внутри обыных  элементов
                    foreach ($model->description as $name_second => $params_second) {

                        if (
                            isset($views['dependent'][$name_second]['depend_of']) && $views['dependent'][$name_second]['depend_of'] && $views['dependent'][$name_second]['depend_of'] == $name
                        ) {
                            echo '<div class="uk-form-row js_cms-dependent_item" data-cms_dependent_input="' . $name . '">';
                            echo $views['dependent'][$name_second]['view'];
                            echo '</div>';
                        }
                    }
                } else {
                    continue;
                }
            }
        }

        return ob_get_clean();
    }

}
