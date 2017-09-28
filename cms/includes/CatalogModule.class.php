<?php

class CatalogModule extends AbstractModule {

    protected $uriconf = array(
        array('~^/?(?:/page\d+)?/?$~', 'index'),
        array('~^/categories/?$~', 'categories'),
        array('~^/fields/?$~', 'fields'),
        array('~^/fieldsets/?$~', 'fieldsets'),
        array('~^/fieldsets/(?P<id>\d+)/?$~', 'fieldsetfields'),
    );

    function index($vars, $uri) {
        $qs = CatalogEntries()->order('title');

        $config = array(
            'model' => 'CatalogEntry',
            'view' => 'core/filters',
            'filters' => array(
                array('field' => 'category', 'title' => 'Категория'),
                array('field' => 'price', 'title' => 'Цена'),
                array('field' => 'enabled', 'title' => 'Товар отображается на сайте', 'widget' => 'yes_no'),
                array('field' => 'edited', 'title' => 'Информация о товаре заполнена', 'widget' => 'yes_no'),
                array('field' => 'has_images', 'title' => 'Товар с фото', 'widget' => 'yes_no'),
            ),
            'search' => array('title'),
        );
        $filters = new NamiFilters($config);
        $qs = $filters->apply_to($qs);

        $current_category = false;
        if (Meta::vars("category")) {
            $current_category = CatalogCategories()->get(Meta::vars("category"));
        }

        $all_categories = CatalogCategories()
            ->filterLevel(2, 0)
            ->treeOrder()
            ->values(array("id", "lvl", "title"));

        return $this->getView('items', array(
                'paginator' => new NamiPaginator($qs, 'core/paginator', 20, "{$this->uri}/page%{page}/?" . $filters->query_string),
                'filters' => $filters,
                'current_category' => $current_category,
                'all_categories' => $all_categories
        ));
    }

    function categories($vars, $uri) {
        $categories = CatalogCategories()->treeOrder()->follow(1)->tree();
        return $this->getView('categories', array(
                'items' => $categories,
        ));
    }

    function fields($vars, $uri) {
        $items = CatalogFields()->sortedOrder()->all();
        return $this->getView('fields', array(
                'items' => $items,
        ));
    }

    function fieldsets($vars, $uri) {
        $items = CatalogFieldSets()->order('sortpos')->all();
        return $this->getView('fieldsets', array(
                'items' => $items,
        ));
    }

    function fieldsetfields($vars, $uri) {
        $fieldset = CatalogFieldSets(array('id' => $vars->id))->first();
        if ($fieldset) {
            $items = CatalogFieldSetFields(array('fieldset' => $fieldset))->order('sortpos')->all();

            $fields_list = array();
            $fields = CatalogFields(array('enabled' => true))->sortedOrder()->all();
            if ($fields) {
                foreach ($fields as $field) {
                    $fields_list[] = array(
                        "id" => $field->id,
                        "title" => $field->title_cms . " (" . $field->field_type->title . ")"
                    );
                }
            }

            return $this->getView('fieldsetfields', array(
                    'items' => $items,
                    'fieldset' => $fieldset,
                    'fields_list' => $fields_list
            ));
        }
    }

    /**
     *   Выводим список кастомных полей
     */
    function handleAjaxRequest($uri) {
        $category_id = Meta::vars('id');
        if (!$category_id) {
            throw new Http404;
        }
        $category = CatalogCategories()->get_or_404($category_id);
        $setfields = CatalogFieldSetFields(array('fieldset' => $category->fieldset))->follow(2)->order('sortpos')->all();
        foreach ($setfields as $fieldset) {
            $title_ = $fieldset->field->title;
            if ($fieldset->field->unit) {
                $title_ .= " (" . $fieldset->field->unit . ")";
            }
            $fieldset->field->title = $title_;

            print new View("modules/CatalogModule/widgets/{$fieldset->field->field_type->editor_widget}", array('field' => $fieldset->field));
        }
    }

}
