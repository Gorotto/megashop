<?

/**
  Базовый оператор NamiQueryCheck
 */
class NamiQCOp {

    /**
      Интерфейс получения значений внутренних полей извне
     */
    function __get($name) {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
    }

    /**
      Генерация значения для like-запросов
      $value - подстрока, которую будем искать
      $leading - boolean, подстрока должна быть началом искомой строки
      $trailing - boolean, подстрока должна быть окончанием искомой строки
     */
    protected function getLikeValue($value, $leading = true, $trailing = true, $words_search = false) {
        // Для начала обезопасим данные в $value
        $value = str_replace('_', '\_', str_replace('%', '\%', $value));

        // Если ищем отдельные слова, нужно понаставить % вместо пробельных символов
        if ($words_search) {
            $value = preg_replace("/\s+/u", "%", $value);
        }

        // И теперь добавим волшебные символы
        return ( $leading ? '' : '%' ) . $value . ( $trailing ? '' : '%' );
    }

}
