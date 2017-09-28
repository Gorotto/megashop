<?

/**
    Составное поле, используется в выборках и сортировках.
    Класс проверяет и разбирает сложное поле в набор join-ов нужных таблиц.
    Поле выглядит так: owner__club__address__city__name.
    Это поле в контексте модели Car означает следующий набор join-ов:
    ... city.name ... from car inner join owner ... inner join club ... inner join city
*/

class NamiComplexField
{
    public $field;  // Последнее поле с алиасом таблицы для выборки или сортировки
    public $extra;	// Дополнительные данные о последнем поле запроса
    public $joins;  // Массив объектов NamiQueryJoin для получения нужной выборки
    public $field_class; // Класс последнего поля таблицы, может пригодиться

    /**
        Конструктор.
        Принимает комплексное поле. В момент создания разбирает его на join-ы и поле-результат.
        $model — имя модели, в контексте которой анализируется поле
        $field — само поле, строка вида 'owner__club__name'
    */
    function __construct( $model, $field )
    {
        $this->field = null;
        $this->joins = array();
        $this->extra = array();
        
        $this->parse( $model, $field );  
    }


    /**
        Рекурсивный разбор составного поля.
        Наполняет поля fields и joins.
        Если что не так, как следует — выбрасывает NamiComplexFieldException
    */
    private function parse( $model, $string, $supalias = '', $null = false )
    {
        $core = NamiCore::getInstance(); // Ядро для разных запросов
    
        $fields = preg_split( '/__/', $string );

        // Вытащим первый элемент — имя поля нашей модели
        $field = array_shift( $fields );
        
        // Проверим наличие поля в модели
        $meta = $core->getNamiModelMetadata( $model );
        if( ! $meta->fieldExists( $field ) )
        {
            throw new NamiException( "There is no '{$field}' field in {$model} model" );
        }
        
        // Проверим, чтобы поле было ссылкой на FK
        if( ! $meta->getField( $field ) instanceof NamiFkDbField )
        {
            throw new NamiException( "'{$field}' field of {$model} can't be used for complex field lookup (it's not a foreign key)", 1 );
        }

        // Получим имя поля, которое нужно проверить - следующее в массиве разобранных полей
        $nextfield = $fields[0];
        // Получим модель того, на что ссылается наше поле
        $nextmodel = $meta->getField( $field )->model;
        $nextmeta = $core->getNamiModelMetadata( $nextmodel );
        
        // На данном этапе мы установили модель, с которой нужно связаться, и проверили следующее поле.
        if( ! $supalias ) $supalias = $core->mapper->getModelAlias( $model );
        // Алиас следующей таблицы — алиас таблицы верхнего уровня __ имя поля
        $alias = "{$supalias}__{$field}";
        
        // Если встретилось null-поле, то все join-ы, начиная с текущего делаем LEFT, а не INNER
        $null = $null || $meta->getField( $field )->null;

		// Создаем join, запоминаем его
		$this->joins[] = new NamiQueryJoin
		( 
			$null ? 'LEFT' : 'INNER',
			$core->mapper->getModelTable( $nextmodel ),
			$alias,
			"{$supalias}.{$field} = {$alias}.{$nextmeta->pkname}"
		);
	
        if( $nextfield == '*' ) {
            $fields = array();
            $fts = array();
            foreach( $nextmeta->getFields() as $f ) {
            	if( $f->fulltext ) {
	            	$fields[] = "{$alias}.". NamiCore::getMapper()->getFieldColumnName( $f );
	            	$fields[] = "{$alias}.". NamiCore::getMapper()->getFieldFulltextColumnName( $f );
	            	$fts[] = "{$alias}.". NamiCore::getMapper()->getFieldFulltextColumnName( $f );
            	}
            }
            
			if( ! $fields ) {
	            throw new NamiException( "Could not perform '*__match' on '{$nextmodel}'. There are no fulltext columns." );
            }
            
            $this->field = join( ', ', $fields );
            $this->extra['fts'] = join( ', ', $fts );
            $this->field_class = 'NamiTextDbField';

			return true;
        } else {
			// Проверим наличие внешнего поля
			if( ! $nextmeta->fieldExists( $nextfield ) )
			{
				throw new NamiException( "There is no '{$nextfield}' field in {$nextmodel} model" );
			}
		}

        // Если внешнее поле тоже FK и после него еще что-то есть - шагнем в рекурсию
        if( $nextmeta->getField( $nextfield ) instanceof NamiFkDbField && count( $fields ) > 1 )
        {
            return $this->parse( $nextmodel, join( '__', $fields ), $alias, $null );
        }

        // Дошли до поля, которое не является FK, либо до конца выбираемого поля
        else
        {
            // Заполним последнее поле
			$fieldObject = $nextmeta->getField( $nextfield );
            $this->field = "{$alias}." . NamiCore::getMapper()->getFieldColumnName( $fieldObject );
            if( $fieldObject->fulltext ) {
            	$this->extra['fts'] = "{$alias}." . NamiCore::getMapper()->getFieldFulltextColumnName( $fieldObject );
            }
            $this->field_class = get_class( $fieldObject );
            return true;
        }

        return false;
    }
}

