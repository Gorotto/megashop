<?php

/*
 * Обработчик для реализации связей многие ко многим
 *
 * пример, после сохранения товара, проверяются все линки на нее:
 *
 *  function afterSave() {
 *      $main_model_data = array(
 *          "id" => $this->id,
 *          "related_ids_new_value" => $this->tags_ids,
 *      );
 *
 *      $link_model_data = array(
 *          "name" => "SimpleCatalogEntryTagLink",
 *          "main_model_field_name" => "entry",
 *          "related_model_field_name" => "tag",
 *      );
 *
 *      LinksProcessor::check_links($main_model_data, $link_model_data);
 *  }
 *
 * пример, перед удалением товара, удаляются все линки:
 *
 * function beforeDelete() {
 *      $remove_params = array(
 *          "links_model_name" => "SimpleCatalogEntryTagLink",
 *          "link_field_name" => "entry",
 *          "link_field_value" => $this->id,
 *      );
 *
 *      LinksProcessor::remove_links($remove_params);
 * }
 *
 */

class LinksProcessor {

    /**
     * Проверка линк таблиц
     *
     * МЕТОД ПОМЕЩАТЬ В ВЫЗОВ afterSave
     *
     * @param array $main_model_data данные основной модели
     * @param array $link_model_data данные модели связей
     *
     *  $main_model_data = array(                   модель для которой реализуются связи
     *       "id" => 0,                                 идентификатор модели
     *       "related_ids_new_value" => array(1,2,3),   идентификаторы внешней модели. текст или массив
     *  );
     *
     *  $link_model_data = array(                   модель связей
     *       "name" => "CatalogEntryAgeLink",           имя модели
     *       "main_model_field_name" => "advice",       название fk поля, ссылки на модель для которой реализуются связи
     *       "related_model_field_name" => "age",       название fk поля, ссылки на модель на котороую реализуются связи
     *  );
     *
     */
    static public function check_links($main_model_data, $link_model_data) {
        if (!is_array($main_model_data['related_ids_new_value'])) {
            $main_model_data['related_ids_new_value'] = json_decode($main_model_data['related_ids_new_value']);
        }

        if ($main_model_data['related_ids_new_value']) {
            foreach ($main_model_data['related_ids_new_value'] as $key => $value) {
                if ($value == "") {
                    unset($main_model_data['related_ids_new_value'][$key]);
                }
            }
        }

        // собираем все связи
        $link_related_ids = array();
        $link_related_ids_ = NamiQuerySet($link_model_data['name'])
            ->filter(array($link_model_data['main_model_field_name'] => $main_model_data['id']))
            ->values($link_model_data['related_model_field_name']);


        foreach ($link_related_ids_ as $id_) {
            if ($id_) {
                $link_related_ids[] = $id_;
            }
        }


        // если нет связей и не заполнено поле - ничего не делаем
        if (!$main_model_data["related_ids_new_value"] && !$link_related_ids) {
            return true;
        }

        // если поле пустое и есть связи - удаляем все связи
        if (!$main_model_data["related_ids_new_value"] && $link_related_ids) {
            $sql = "DELETE FROM " . mb_strtolower($link_model_data['name']) . " WHERE " . $link_model_data['main_model_field_name'] . "=" . $main_model_data['id'];
            NamiCore::getBackend()->cursor->execute($sql);
            return true;
        }

        $related_similar_ids = array_intersect($main_model_data['related_ids_new_value'], $link_related_ids);
        $related_ids_to_kill = array_diff($link_related_ids, $related_similar_ids);

        $related_ids_to_add = array_diff($main_model_data['related_ids_new_value'], $related_similar_ids);

        if (!$related_ids_to_add && !$related_ids_to_kill) {
            return true;
        }

        if ($related_ids_to_add) {
            foreach ($related_ids_to_add as $related_id) {
                NamiQuerySet($link_model_data['name'])
                    ->create(array(
                        $link_model_data['main_model_field_name'] => $main_model_data['id'],
                        $link_model_data['related_model_field_name'] => $related_id,
                ));
            }
        }

        if ($related_ids_to_kill) {
            $sql = "DELETE FROM " . mb_strtolower($link_model_data['name']) . " where " . $link_model_data['related_model_field_name'] . " in (" . implode(",", $related_ids_to_kill) . ") and " . $link_model_data['main_model_field_name'] . "=" . $main_model_data['id'];
            NamiCore::getBackend()->cursor->execute($sql);
        }
    }

    /**
     * Удаление линков.
     *
     * Метод помещаеть в beforeDelete.
     *
     * первый параметр:
     *   $remove_params = array(
     *       "links_model_name" => "PublicationCatalogEntryLink",
     *       "link_field_name" => "entry",
     *       "link_field_value" => 1,
     *   );
     *
     * links_model_name     - модель для хранения линков
     * link_field_name      - название поля по которому выбираются линки
     * link_field_value     - значение этого поля
     *
     * т.е. удаляет все линк записи модели «links_model_name» имеющее поле «link_field_name» со
     * значением «link_field_value»
     *
     *
     * Если указан второй параметр, то будут проверены записи связанной модели
     * например:
     *  $check_params = array(
     *      "publication" => "entries_ids"
     *  );
     *
     * перед удлаением линка будет проверена модель ссылающаяся по fk полю
     * указанному в качестве ключа (пример - publication). В этой модели будет
     * искаться поле указанное как параметр (пример - entries_ids).
     * Если в этом поле имеется идентификатор основной модели - метод его удалит
     * и запишет данные.
     *
     * Т.е. до вызова метода:
     * entries_ids = 1,2,3
     * после вызова:
     * entries_ids = 1,2
     *
     */
    static public function remove_links($remove_params, $check_params = false) {
        $links = NamiQuerySet($remove_params["links_model_name"])
            ->filter(array(
                $remove_params["link_field_name"] => $remove_params["link_field_value"]
            ))
            ->all();

        if ($links) {
            foreach ($links as $link) {

                if ($check_params) {
                    foreach ($check_params as $field_name => $check_field) {
                        $related_record = $link->$field_name;

                        if ($related_record) {
                            $old_ids = json_decode($related_record->$check_field);
                            $remove_ids = array($link->$remove_params["link_field_name"]->id);
                            $new_ids = array_diff($old_ids, $remove_ids);

                            $related_record->$check_field = json_encode($new_ids);

                            //вызываем хидден чтобы опять не дергать проверку линков
                            $related_record->hiddenSave();
                        }
                    }
                }


                $link->delete();
            }
        }
    }

}

?>
