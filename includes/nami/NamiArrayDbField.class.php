<?
/**
*   Поле-массив
*   Definition инициализоровать так:
*       'my_array_field' => new NamiArrayDbField(array('type' => 'NamiIntegerDbField'))
*
*       После type можно указать параметры, необходимы для работы поля, указанного в type,
*       такие как пути к каталогам, ограничения длины и т.п.
*   
*   Как работает:
*   1. Значения в БД хранятся в одном текстовом поле в виде JSON-массива, каждый элемент массива хранится так,
*       как хранится в БД подлежащий тип поля.
*       
*   2. Получение значения:
*       Возвращает объект, реализующий интерфейсы ArrayAccess и Iterator.
*   
*       Можно итерировать по массиву с помощью цикла foreach:
*           foreach ($obj->my_array_field as $item) {
*               print $item;
*           }
*   
*       Можно узнать длину массива:
*           print $obj->my_array_field->length;
*   
*       Можно получать значения по индексу:
*           for ($i = 0; $i < $obj->my_array_field->length; $i++) {
*               print $obj->my_array_field[$i];
*           }
*       
*       Каждый элемент массива ведет себя как обычное поле ORM'а соответствующего типа.
*   
*   3. Изменение значения целиком:
*       $obj->my_array_field = array(10, 20, 30); // заново инициализировать значения
*       $obj->my_array_field = null; // сбросить все значения, массив пуст
*   
*   4. Изменение отдельных значений:
*       $obj->my_array_field[5] = 42;   // изменение по указанному смещению
*       $obj->my_array_field[] = 1337;  // добавление значения в конец
*       unset($obj->my_array_field[0]); // удалить значение с индексом 0, массив станет короче
*
*   5. Фильтрация по полям-массивам невозможна.
*/
class NamiArrayDbField extends NamiDbField {
	protected $valueClassname = 'NamiArrayDbFieldValue';
	
    /**
    *   Конструктор
    */
    function __construct(array $params = array()) {
        $params['db_field'] = $this;

        parent::__construct($params);
        
        /*  Сделаем так, чтобы наше поле значения NULL хранило
            в виде объекта со значение null, а не просто null. */
        if ($this->localized) {
            foreach (NamiCore::getAvailableLanguages() as $lang) {
                $this->setValue(null, $lang);
            }
        } else {
            $this->setValue(null);
        }
        $this->markClean();
	}
}
