<?php

class CsvCategoriesProcessor extends CsvProcessor {

    private $CATALOG_CATEGORY_MODEL_NAME = "CatalogCategory";
    private $categories_cache = array();
    private $categories_to_enabled_ids = array();
    public $categories_add = 0;
    public $categories_del = 0;
    public $categories_upd = 0;

    function __construct($filepath) {
        parent::__construct($filepath);

        $this->load_catalog_tree();
    }

    function print_log() {
        echo "Добавлено {$this->categories_add} позиций для категорий каталога<br>";
        echo "Обновлено {$this->categories_upd} позиций для категорий каталога<br>";
        echo "Отключено {$this->categories_del} позиций для категорий каталога<br>";
        echo "<br>";
    }

    /**
     * кеширование дерева для проверки на наличие
     */
    private function load_catalog_tree() {
        $categories = NamiQuerySet($this->CATALOG_CATEGORY_MODEL_NAME)->filterLevel(2, 0)->treeOrder()->tree();

        if ($categories) {
            foreach ($categories as $category) {
                $this->load_catelog_tree_branch($category, "");
            }
        }
    }

    /**
     * рекурсивная загрузка веток дерева
     */
    private function load_catelog_tree_branch($category, $parent_code) {
        $this->categories_cache[$category->onec_code] = array(
            "parent_onec_code" => $parent_code,
            "id" => $category->id,
            "enabled" => $category->enabled,
            "title" => $category->title
        );

        $sub_categories = $category->getChildren();
        if ($sub_categories) {
            foreach ($sub_categories as $sub_category) {
                $this->load_catelog_tree_branch($sub_category, $category->onec_code);
            }
        }
    }

    /**
     * пуск!
     */
    function process() {
        $this->delimiter = "&";
        $this->detect_dublicates();

        while (!feof($this->file_link)) {
            $line = fgets($this->file_link);
            if ($line) {
                $cur_fields = $this->prepare_csv_line($line);

                $this->process_line($cur_fields);
            }
        }
        fclose($this->file_link);

        $this->disable_categories();
        $this->detect_dublicates();
    }

    /**
     * Обработка строки выгрузки
     */
    private function process_line($data) {
        if (array_key_exists($data[0], $this->categories_cache)) {
            $this->update_category($data);
        } else {
            $this->create_category($data);
        }
    }

    /**
     * Обновление данных по категории
     */
    private function update_category($data) {
        $db_category = $this->categories_cache[$data[0]];

        if (
            $this->categories_cache[$data[0]]['parent_onec_code'] != $data[1] ||
            $db_category["title"] != $data[2] ||
            $db_category["enabled"] != true
        ) {
            $category = NamiQuerySet($this->CATALOG_CATEGORY_MODEL_NAME)->get(array("onec_code" => $data[0]));


            //перемещение ветки
            //как правило не нужно 
            //потому что в 1С категории храняться как попало, 
            //а на сайте их раскидывают вручную
//            if ($this->categories_cache[$data[0]]['parent_onec_code'] != $data[1]) {
//                if ($data[1]) {
//                    $parent_category = NamiQuerySet($this->CATALOG_CATEGORY_MODEL_NAME)->get(array("onec_code" => $data[1]));
//                } else {
//                    $parent_category = $this->root_category;
//                }
//
//                $category->putAsFirstChild($parent_category);
//                $this->categories_cache[$data[0]]['parent_onec_code'] = $data[1];
//            }


            if (
                $db_category["title"] != $data[2] ||
                $db_category["enabled"] != true
            ) {
                $category->title = $data[2];
                $category->enabled = true;
            }

            $category->save();

            $this->categories_cache[$data[0]] = array(
                "parent_onec_code" => $data[1],
                "id" => $category->id,
                "enabled" => true,
                "title" => $data[2]
            );
            $this->categories_upd++;
        }

        $this->categories_to_enabled_ids[] = $db_category["id"];
    }

    /**
     * Создаем категорию
     */
    private function create_category($data) {
        if ($data[1] == "") {
            $parent_category = NamiQuerySet($this->CATALOG_CATEGORY_MODEL_NAME)->get(array("lvl" => 1), false);
        } else {
            $parent_category = NamiQuerySet($this->CATALOG_CATEGORY_MODEL_NAME)->get(array("onec_code" => $data[1]), false);
        }

        if ($parent_category) {
            $new_category = NamiQuerySet($this->CATALOG_CATEGORY_MODEL_NAME)->createLastChild($parent_category->id, array(
                "onec_code" => $data[0],
                "title" => $data[2],
                "enabled" => true
            ));

            $this->categories_add += 1;

            $this->categories_to_enabled_ids[] = $new_category->id;
            $this->categories_cache[$new_category->onec_code] = array(
                "parent_onec_code" => $parent_category->onec_code,
                "id" => $new_category->id,
                "enabled" => $new_category->enabled,
                "title" => $new_category->title
            );
        } else {
            throw new Exception("Ошибка файла выгрузки. Нет родительской категории с кодом " . $data[1]);
        }
    }

    /**
     * Выключение категорий
     */
    private function disable_categories() {
        $this->categories_del = NamiQuerySet($this->CATALOG_CATEGORY_MODEL_NAME)
            ->filter(array(
                "id__notin" => $this->categories_to_enabled_ids,
                "lvl__ne" => 1
            ))
            ->count();


        if ($this->categories_to_enabled_ids) {
            $query = "update `" . mb_strtolower($this->CATALOG_CATEGORY_MODEL_NAME) . "` set enabled = 0 " .
                "where id not in ('" . implode("','", $this->categories_to_enabled_ids) . "') " .
                "or onec_code IS NULL";
        } else {
            $query = "update `" . mb_strtolower($this->CATALOG_CATEGORY_MODEL_NAME) . "` set enabled = 0";
        }

        NamiCore::getBackend()->cursor->execute($query);
    }

    /**
     * Проверка на дублирование информации
     */
    private function detect_dublicates() {
        $query = "SELECT `onec_code`, count(`onec_code`) FROM `" . mb_strtolower($this->CATALOG_CATEGORY_MODEL_NAME) . "` GROUP BY `onec_code` HAVING COUNT(`onec_code`) > 1";

        $backend = NamiCore::getBackend();
        $backend->cursor->execute($query);
        $data = $backend->cursor->fetchAll();

        if ($data) {
            $codes = array();
            foreach ($data as $row) {
                $categories_title = NamiQuerySet($this->CATALOG_CATEGORY_MODEL_NAME)
                    ->filter(array("onec_code" => $row[0]))
                    ->values("title");

                $codes[] = implode(", ", $categories_title) . "(" . $row[0] . ")";
            }

            throw new Exception("В каталоге присутствуют дублирующие категории. " . implode("; ", $codes));
        }
    }

}

?>
