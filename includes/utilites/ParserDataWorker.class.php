<?
/**
    Из полученной строки с данными создает записи в базе данных на основе определенных правил

    TODO:
    айтемы, у которых есть parent: их перемещения в дереве
*/
abstract class ParserDataWorker {

    /**
        Готовим данные и пишем в базу специальным методом create_or_update
    */
    abstract function work($data);
    
    /**
        Штука, которая вставляет fields из data в model.
        
        Про fields нужно написать отдельно:
        
        Ключ - название поля в модели $model
        Значение - список параметров (все параметры необязательны):
            - 'dataname' => 'id' - если название поля в выгрузке и в моделе отличаются.
            - 'primary' => true - обозначает поле, по которому нужно искать записи в базе. Если параметр пропущен, 
                все записи будут просто добавляться в базу, без попытки найти и обновить существующую.
            - 'fk' => array('model' => 'Country', 'pk' => 'sku')) - показывает, что поле - это foreing key к другой модели, 
                и в базу нужно класть id записи этой другой модели.

        Еще при вызове create_or_update последним параметром можно задать, чтобы функция даже не пыталась создавать записи,
        а только обновляла существующие, выбранные по primary ключу (если конечно этот ключ задан)
    */
    protected function create_or_update($model, $fields, $data, $create = true) {        
        // поищем primary key
        $pk_name = null;
        foreach($fields as $name => $params) {
            if (@$params['primary']) {
                $pk_name = $name;
                $pk_dataname = @$params['dataname'] ? $params['dataname'] : $name;
                break;
            }
        }
        // если есть pk можно по нему поискать entry
        $entry = null;
        if ($pk_name) {
            $entry = NamiQuerySet($model)->filter(array("{$pk_name}" => $data[$pk_dataname]))->first();
        }
        // если entry подразумевался и нашелся то обновим его, иначе просто добавим
        // это очень классно, потому что скрипт может работать с двумя типами выгрузок:
        // - когда нужно обновлять и добавлять (указываем primary)
        // - когда нужно только добавлять (не указываем primary)
        if ($entry) {
            // насколько я понимаю, проверять, изменились ли данные не нужно
            // метод save сам по себе сильно умный, и все равно проверит перед update есть ли чо новое
            $values = $this->get_values($fields, $data, $pk_name, $skip_pk = true);
            $entry->copyFrom($values);
            $entry->save();
        } elseif ($create) {
            $values = $this->get_values($fields, $data, $pk_name);
            NamiQuerySet($model)->create($values);
        }
    }

    /**
        Собираем пары 'поле_модели' => 'значение' в массив
    */
    private function get_values($fields, $data, $pk_name, $skip_pk = false) {
        $values = array();
        foreach ($fields as $name => $params) {
            // primary key при обновлении пропускаем
            if ($skip_pk && $name == $pk_name) {
                continue;
            }
            $dataname = @$params['dataname'] ? $params['dataname'] : $name;
            if (@$params['fk']) {
                // если это поле foreing key - нам в базу нужно записать id позиции из связанной модели
                // попробуем его достать
                $entry = NamiQuerySet($params['fk']['model'])->filter(array(
                    $params['fk']['pk'] => $data[$dataname]
                ))
                ->first();
                // если не можем найти связанную запись, останавливаем выгрузку и идем разбираться в чем дело
                if (!$entry) {
                    throw new ParserException("Не удается найти {$params['fk']['model']} c {$params['fk']['pk']} = {$data[$dataname]}");
                }
                $data[$dataname] = $entry->id;
            }
            $values[$name] = $data[$dataname];
        }
        return $values;
    }   
}