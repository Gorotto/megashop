<?php

/**
    Простой и приятный шаблонный движок.
    Позволяет разделить бизнес-логику от логики отображения.
    Язык шаблонов — PHP.
*/

class View
{
    private $vars;  # Переменные шаблона
    private $file;  # Имя файла шаблона

    private $fetching; // Означает, что процесс отрисовки шаблона активен
	private $bufferingLevel; // Уровень вложенности буферизации

    private static $context_stack = array(); # Стек контекстов вызова

    public static $preSuffixes = array(); // Суффиксы, используемые ДО основного файла
    public static $postSuffixes = array(); // Суффиксы, используемые ПОСЛЕ основного файла
    
    protected $block_stack = array();   // Стек блоков (они могут быть вложенными)
    protected $parent_view = null;      // Родительский шаблон (автоматически вызывается после fetch, если указан)
    protected $filter_stack = array();  // Стек фильтров
    
    /**
        Инициализация класса
    */
    public static function init()
    {
        // Массив каталогов, в которых могут быть шаблоны
        $paths = array
        (
            "{$_SERVER['DOCUMENT_ROOT']}/views",
            "{$_SERVER['DOCUMENT_ROOT']}/cms/views",
        );
        // Автивируем шаблоны
        set_include_path( join( PATH_SEPARATOR, array_merge( $paths, array( get_include_path() ) ) ) );
    }
    
	public static function addSuffix( $suffix, $high_priority = true ) {
		if( $high_priority ) {
			$array = & self::$preSuffixes;
		} else {
			$array = & self::$postSuffixes;
		}
		if( ! in_array( $suffix, $array ) ) {
			$array[] = $suffix;
		}
    }

	public static function removeSuffix( $suffix, $high_priority = true ) {
		if( $high_priority ) {
			$array = & self::$preSuffixes;
		} else {
			$array = & self::$postSuffixes;
		}
		
		$pos = array_search( $suffix, $array );
		if( $pos !== false ) {
			unset( $array[ $pos ] );
			$array = array_values( $array );
		}
    }


    /**
        Конструктор
        $file — имя файла шаблона
        $vars — массив переменных шаблона
    */
    function __construct( $file = null, $vars = null ) {
        $this->file = $file;
        $this->vars = is_array( $vars ) ? $vars : array();
		$this->fetching = false;
		$this->bufferingLevel = 0;
    }

	/**
		Массовая установка переменных шаблона
		$vars ассоциативный массив переменных шаблона, все они будут установлены
		$mandatoryNames массив имен переменных, которые будут инициализированы в любом случае
	*/
	public function setVars( $vars, $mandatoryNames = array() ) {
		// Скопируем наши значения
		if( is_array( $vars ) ) {
			foreach( $vars as $name => $value ) {
				$this->{ $name } = $value;
			}
		}
		
		// Теперь проверим обязательные, и если у нас их еще нет — сделаем их равными null
		foreach( $mandatoryNames as $name ) {
			if( ! $this->has( $name ) ) {
				$this->{ $name } = null;
			}
		}
	}

    /**
        Перегрузка установки полей объекта — запись переменных шаблона
        $name имя переменной
        $value значение переменной
    */
    public function __set( $name, $value )
    {
        $this->vars[ $name ] = $value;
    }

    /**
        Получение установленной переменной
    */
    public function &__get( $name )
    {
        # Проверим на вызов HTML entities
        if( ( $pos = strpos( $name, 'HE' ) ) !== false )
        {
            # Если в конце имение есть HE - удалим HE, а результат пропустим через htmlspecialchars()
            $name = substr( $name, 0, $pos );
            $result = htmlspecialchars( $this->$name );
            return $result;
        }

        # Проверим на вызов HTML Format
        if( ( $pos = strpos( $name, 'HF' ) ) !== false )
        {
            # Если в конце имение есть HF - удалим HF, а результат пропустим через $this->HF()
            $name = substr( $name, 0, $pos );
            return $this->HF( $this->$name );
        }

        # Пробуем выбрать переменную из собственного массива
        if( array_key_exists( $name, $this->vars ) )
        {
            return $this->vars[ $name ];
        }

        # Пробуем выбрать переменную из контекста
        foreach( self::$context_stack as $context )
        {
            if( array_key_exists( $name, $context['vars'] ) )
            {
                return $context['vars'][ $name ];
            }
        }

        # Переменная не найдена нигде — делаем warning дабы предупредить об использовании несуществующих переменных
        trigger_error( "Non-existent view variable <b>\$this->{$name}</b> in <b>{$this->file}.view.php</b>", E_USER_NOTICE );
        return null;
    }
    
    /**
    	Получение имени файла шаблона с учетом суффиксов и всего такого остального
    	$file - имя шаблона, как оно было передано
    	возвращает null или реальное имя файла, которое нужно require-ть
    */
    protected function getRealFileName( $file ) {
    	// Первым делом наполним массив возможных имен файла
		foreach( array_merge( self::$preSuffixes, array( '' ), self::$postSuffixes )  as $suffix ) {
			$filename = "{$file}{$suffix}.view.php";
			if( Meta::file_exists( $filename, true ) ) {
				return $filename;
			}
		}
		return null;
    }
    
    /**
    *   Получение пути текущего файла, как он был передан в конструктор шаблона.
    *   Возвращает строку, пустую если в переданном имени не было пути (было только имя).
    *   Trailing slash included.
    */
    protected function getFilePath() {
        if (preg_match('/^(.+\/)[^\/]+$/', $this->file, $matches)) {
            return $matches[1];
        } else {
            return '';
        }
    }

    /**
        Заполнение шаблона и возврат получанного результата
    */
    public function fetch( $file = null )
    {
		try {
			# Если файл передан — используем его, иначе — наш файл
			if( ! $file ) {
				$file = $this->file;
			}
			
			// Проверим существование файла, который собираемся подключить
			$filename = $this->getRealFileName( $file );
			if( ! $filename ) {
				throw new Exception( "View '{$file}' not found" );
			}
	
			# Помещаем текущее окружение в стек контекстов
			array_unshift( self::$context_stack, array( 'file' => $file, 'vars' => $this->vars ) );
			$this->fetching	= true;
			
			# Буферизуем вывод
			ob_start();
	
			# Просто подключаем файл шаблона
			require( $filename );
			
			# Если внезапно оказался указан родительский шаблон (extends() был вызван) - заменим шаблон
			if ($this->parent_view) {
                $parent_view = $this->parent_view;
                $this->parent_view = null;
                $this->replaceWith($parent_view);
			}
	
			// Завершаем сбор данных
    		$this->finishBuffering();

			# Очищаем стек контекстов
			array_shift( self::$context_stack );
			$this->fetching	= false;
			// Завершим сбор данных

	
			# Получаем вывод из буфера, буфер сбрасываем, содержимое — возвращаем
			return ob_get_clean();
		} catch (HttpException $exception) {
            $this->finishBuffering();
            return Builder::process_http_exception($exception);
		} catch (Exception $exception) {
			if( Builder::developmentMode() ) {
				$dump = "<html><h2>" . $exception->getMessage() . "</h2><h4>" . get_class( $exception ) ." in " . $exception->getFile() . " on line " . $exception->getLine() . ".</h4>";
				$dump .= Meta::formatDebugTrace( $exception->getTrace() );
				$dump .= "</html>";
				trigger_error( $dump, E_USER_ERROR );
			} else {
				trigger_error( $exception->getMessage(), E_USER_ERROR );
			}
			return '';
		}
    }

    /**
        Приведение объекта к строке — заменим его вызовом fetch(), весьма пригодится.
    */
    public function __toString()
    {
		return $this->fetch( $this->file );
    }

    /**
        Экранирование Html Entities для краткости — HE
        $string строка
        Возвращает экранированную строку
    */
    public function HE( $string )
    {
        return htmlspecialchars( $string );
    }

    /**
        Форматирование текста для вывода в HTML
        Html Format, для краткости — HF
    */
    public function HF( $text )
    {
        # Удаляем все html-теги
        $text = strip_tags( $text );

        # Заменяем \n на <br>, и на этом заканчиваем
        return str_replace( "\n", '<br/>', $text );
    }

    /**
        Получение всех переменных шаблона в виде массива, может пригодиться для передачи в другой шаблон
    */
    public function getVars()
    {
        return $this->vars;
    }

    /**
    *	Проверка наличия переменной шаблона
    *	$name - имя переменной
    *   $own_only - искать только в собственных переменных, на обращаясь в родительские контексты
    *	Возвращает true или false в зависимости от того, передана такая переменная шаблону или нет
    */
    function has( $name, $own_only = false ) {
    	// Посмотрим в собственном массиве
        if( array_key_exists( $name, $this->vars ) ) {
        	return true;
        }
        if (! $own_only) {
    		// Посмотрим в контекстах выше
            foreach( self::$context_stack as $context ) {
                if( array_key_exists( $name, $context['vars'] ) ) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
    *	Представление объекта для трассировщика
    */
    function toTraceString() {
    	return get_class( $this )."[ {$this->file} ]";
    }
    
	function startBuffering() {
    	if( ! $this->fetching ) {
    		throw new Exception( 'Невозможно начать сбор данных шаблона вне шаблона.' );
    	}
		$this->bufferingLevel++;
		ob_start();
	}
    
    function stopBuffering() {
    	if( ! $this->fetching ) {
			throw new Exception( 'Невозможно завершить сбор данных шаблона вне шаблона.' );
    	}
    	if( $this->bufferingLevel > 0 ) {
	    	$this->bufferingLevel--;
	    }
		return ob_get_clean();
    }
    
    function finishBuffering() {
		// Соберем все несобранное
		while( $this->bufferingLevel > 0 ) {
			ob_end_clean();
			$this->bufferingLevel--;
		}
		$this->bufferingLevel = 0;
    }
    
    /**
    *	Замена текущего шаблона переданным.
    *	$name — имя нового шаблона, который должен заменить текущий.
    */
    function replaceWith( $name ) {
    	// Заменим файл
    	$this->file = $name;
    	
    	// Если отрисовка была активна — продолжим ее
    	if(	$this->fetching ) {
			// Завершим сбор данных
    		$this->finishBuffering();

    		// Сбросим все, что успел нарисовать предыдущий шаблон
			ob_clean();
			
			// Выведем новый шаблон. print используется потому, что мы _уже_ внутри вызова fetch, а replaceWith был вызван из текста шаблона :D
			print $this->fetch();
    	}

    	return $this;
    }
    
    /**
    *   Проверка нахождения внутри шаблона с заданным именем
    */
    function inside( $file, $useContext = true ) {
        if( $this->file == $file ) {
            return true;
        }
        if( $useContext ) {
            foreach( self::$context_stack as $context ) {
                if( $context['file'] == $file ) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
    *   Начало блока
    *   После вызова все выводимые данные будут попадать в специальный буфер,
    *   содержимое которого после вызова endBlock будет записано в переменную
    *   текущего шаблона с именем $name.
    */
    function beginBlock($name) {
        array_push($this->block_stack, $name);
        $this->startBuffering();
    }
    
    /**
    *   Окончание блока
    *   Завершает сбор вывода в буфер и записывает его содержимое в переменную,
    *   имя которой было указано в предыдущем вызове beginBlock($name).
    *   По умолчанию заменяет значение переменной, однако если указать $replace = false,
    *   значение будет дописано к имеющемуся.
    */
    function endBlock($replace = true) {
        $varname = array_pop($this->block_stack);
        $text = $this->stopBuffering();
        if (! $replace && $this->has($varname)) {
            $this->$varname .= $text;
        } else {
            $this->$varname = $text;
        }
    }
    
    /**
    *   Сделать так, что текущий шаблон будет заменен указанным.
    *   Аналог replaceWith($name) в конце текста шаблона.
    *   Можно вызывать несколько раз, сработает последний.
    */
    function extend($name) {
        $this->parent_view = $name;
    }
    
    /**
    *   Начало блока вывода без пробелов между тегами
    */
    function beginSpaceless() {
        $this->startBuffering();
    }

    /**
    *   Конец блока вывода без пробелов между тегами
    */
    function endSpaceless() {
        $text = $this->stopBuffering();
        $spaceless = preg_replace('/(?<=>)\s+(?=<)/', '', $text);
        echo $spaceless;
    }
    
    /**
    *   Начало фильтра
    *   $method - метод фильтрации, который нужно применить, по факту - имя существующего
    *   метода шаблона или php callback, принимающий один аргумент (текст)
    *   и возвращающий текст.
    *   Можно опционально передать несколько аргументов они будут переданы после текста в метод
    *   После вызова все выводимые данные будут попадать в специальный буфер,
    *   содержимое которого после вызова endFilter будет обработано методом $method
    *   и выведено.
    *   Пример:
    *       Где-то в недрах приложения:
    *       $userinput = $_POST['text']; // В текст плохой человек ввел '<script>alert("PWNED!");</script>';
    *       ...
    *       Тем временем во view:
    *       <? $this->beginFilter('htmlentities', ENT_NOQUOTES, 'UTF-8') ?>
    *       <?= $this->userinput ?>
    *       <? $this->endFilter() ?>
    *       Теги не сработали и XSS-атака не удалась :)
    *   Для применения с фильтрами шаблонов создан класс HtmlFilter, методы которого
    *   можно передавать в beginFilter, типа $this->beginFilter('HtmlFilter::nice_filter');
    */
    function beginFilter($method) {
        if (method_exists($this, $method)) {
            $callback = array($this, $method);
        } elseif (is_callable($method)) {
            $callback = $method;
        } else {
            throw new Exception("Invalid view filter '$method'");
        }
        
        $arguments = array_slice(func_get_args(), 1);

        array_push($this->filter_stack, array($callback, $arguments));
        $this->startBuffering();
    }
    
    
    /**
    *   Конец блока вывода без пробелов между тегами
    */
    function endFilter() {
        if (! $this->filter_stack) {
            throw new Exception("Empty filter stack. endFilter() calls do not match with beginFilter()s.");
        }
        list($callback, $arguments) = array_pop($this->filter_stack);
        $text = $this->stopBuffering();
        array_unshift($arguments, $text);
        $processed = call_user_func_array($callback, $arguments);
        echo $processed;
    }
}