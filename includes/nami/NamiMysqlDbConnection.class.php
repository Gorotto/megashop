<?

class NamiMysqlDbConnection extends NamiDbConnection {

    protected $handler = NULL;
    protected $cursor = NULL;

    /**
      Подключение к базе данных.
      При имеющемся подключении не делает ничего.
      Возвращает true, генерирует Exception на неудачу.
     */
    function open() {
        // Есть соединение с базой?
        if ($this->handler) {
            // Он пингуется — все в порядке, ничего не делаем.
            if (mysqli_ping($this->handler) === true)
                return true;

            // Не пингуется, соединимся заново, закрыв соединение
            $this->close();
            $this->open();
        } else {
            // Соединения нету, нужно установить
            // Соединяемся
            $handler = mysqli_connect(
                $this->host, $this->user, $this->password, $this->name, $this->port ? $this->port : null
                // всегда открываем новое соединение
            );

            if (!$handler) {
                throw new NamiException(mysqli_error($handler));
            }

            // Попробуем выбрать базу данных, проверим результат
            if (mysqli_select_db($handler, $this->name) !== true) {
                $error = mysqli_error($handler);
                mysqli_close($handler);
                throw new NamiException($error);
            }

            // Установим кодировку соединения
            if ($this->charset) {
                mysqli_query($handler, "SET NAMES '{$this->charset}'");
                if (mysqli_error($handler)) {
                    $error = mysqli_error($handler);
                    mysqli_close($handler);
                    throw new NamiException($error);
                }
            }

            // Все прошло хорошо - сохраним линк на соединение
            $this->handler = $handler;
            $this->cursor = NULL;
        }
    }

    /**
      Закрытие соединения
     */
    function close() {
        if ($this->handler) {
            mysqli_close($this->handler);
        }

        $this->handler = NULL;
        $this->cursor = NULL;

        return true;
    }

    /**
      Закавычивание строки
     */
    function escape($string) {
        $this->open();
        return $this->handler->real_escape_string($string);
    }

    /**
      Закавычивание имени объекта БД
     */
    function escapeName($name) {
        if (mb_substr($name, 0, 1, 'utf-8') == '`' && mb_substr($name, mb_strlen($name, 'utf-8') - 1, 1, 'utf-8') == '`') {
            return $name;
        }

        return "`{$name}`";
    }

    /**
      Получение значения автоинкрементного поля, установенного базой
     */
    function getLastInsertId(NamiDbCursor $cursor = NULL, $table = '', $pkname = '') {
        return mysqli_insert_id($this->handler);
    }

    /**
      Получение handlerа соединения
     */
    function getHandler() {
        $this->open();
        return $this->handler;
    }

    /**
      Получение курсора соединения
     */
    function getCursor() {
        $this->open();

        if (!$this->cursor) {
            $this->cursor = new NamiMysqlDbCursor($this);
        }

        return $this->cursor;
    }

}
