<?
die;
if ($uri == '/resave_pdf') {
    /*
     * Генерируем файл с табличкой характеристик
     */

    $result = array(
        'msg' => '',
        'error' => '',
    );

    try {
// тут наша лиейка товаров, например ZOTA "Lux"
        $entry = CatalogEntries()->get_or_404($category_id);

//var_dump(!$this->isDirty('sortpos'));
//var_dump($entry && !$this->isDirty('sortpos'));
// тут все модификации линейки, например ZOTA "Lux-3"
        $entry_mods = CatalogEntryMods(array('enabled' => true, 'category' => $entry->id))->sortedOrder()->all();

// а тут не забываем про саму категорию товаров, например "Электрокотлы"
//$category = $entry->category;
// Расширенные поля привязываются как раз к категории, а не к товару (теже "Электрокотлы")
        $extrafields = CatalogFieldSetFields(array('fieldset' => $entry->category->fieldset))
                ->follow(1)
                ->sortedOrder()
                ->all();

// TODO: высокая нагрузка, по-возможности договориться переделать отдельной генерацией
// тут будет компоновка из доп. полей для заголовков таблицы
        $extrafields_heads = array();
//$updated = true;

        foreach ($extrafields as $extrafield) {
//if ($this->old_mod && $this->{$extrafield->field->name} != $this->old_mod->{$extrafield->field->name}) {
//    $updated = true;
//}
            $extrafields_heads[] = array(
                'title' => $extrafield->field->title,
                'title_hint' => $extrafield->field->title_hint,
                'unit' => $extrafield->field->unit,
            );
        }

// шаблон вввода таблицы с характеристиками
        $html_mods = new View('catalog/block-mods', array(
            'extrafields_heads' => $extrafields_heads,
            'entry_mods' => $entry_mods,
            'entry' => $entry
                )
        );

// получили записали в буфер html
        $html_mods = $html_mods->fetch();

// далее проверяем директорию выделенную под файлы для таблиц характеристик
        $direrctory = "/static/uploaded/mods/";
        $full_path = "{$_SERVER['DOCUMENT_ROOT']}{$direrctory}";
        if (!file_exists($full_path)) {
            mkdir($full_path, 0777, true);
        }

// обертка для библиотеки mpdf для создания .pdf-файлов (/includes/3rdparty/mpdf)
        $mpdf = MPDFLibrary::create();
        $mpdf->charset_in = "utf8";

        $stylesheet = file_get_contents("{$_SERVER['DOCUMENT_ROOT']}/static/css/style.css"); /* подключаем css */
        $mpdf->WriteHTML($stylesheet, 1); // 1 - подключить стили

        $mpdf->WriteHTML($html_mods, 2); // 2 - значит только html на вывод (без стилей)
        $filename = "{$entry->category->name}_{$entry->name}.pdf";

// Если у нас уже был создан файл с характеристиками, то просто удалим его
        if ("{$full_path}{$filename}" != "{$_SERVER['DOCUMENT_ROOT']}$entry->mods_file" && file_exists("{$_SERVER['DOCUMENT_ROOT']}$entry->mods_file")) {
            unlink("{$_SERVER['DOCUMENT_ROOT']}$entry->mods_file");
        }

        $mpdf->Output("{$full_path}{$filename}", 'F'); // 'F' - сохранить в файл
// в саму линейку товаров запишем путь до нового файла
        $entry->mods_file = "{$direrctory}{$filename}";
        $entry->hiddenSave();

        $result['msg'] = ".pdf - файл с модификациями товара: {$entry->title} успешно создан";
    } catch (NamiException $e) {
        $result['msg'] = "Ошибка! Не удалось сохранить .pdf - файл с модификациями товара: {$entry->title}. Код ошибки: " . $e->getCode();
        $result['error'] = $e->getMessage();
    }
    header('Content-Type: application/json');
    echo json_encode($result);
}
?>