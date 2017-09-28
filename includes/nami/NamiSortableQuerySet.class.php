<?
class NamiSortableQuerySet extends NamiQuerySet
{
    function only( $fields ) {
    	if( ! is_array( $fields ) ) {
    		$fields = explode( ' ', $fields );
    	}

        array_push( $fields, 'sortpos' );

    	return parent::only( $fields );
    }
    
    function sortedOrder() {
        return $this->orderAsc( 'sortpos' );
    }

    function sortedOrderAsc() {
        return $this->orderAsc( 'sortpos' );
    }

    function sortedOrderDesc() {
        return $this->orderDesc( 'sortpos' );
    }
    
    /**
        Переупорядочивание списка записей.
        $sorted - массив идентификаторов записей в том порядке, который они должны иметь.
        Сортируются только переданные записи, остальные остаются на своих местах.
    */
    function reorder( array $sorted ) {
        // Удалим дубликаты из массива порядка
        $sorted = array_unique( $sorted );
        
        // Выбираем имеющиеся объекты
        $objects = $this->filter( array( 'pk__in' => $sorted ) )->sortedOrder()->all();

        // Карта имеющихся объектов по их PK
        $map = Meta::getAssocArray( $objects, NamiCore::getInstance()->getNamiModelMetadata( $this->model )->pkname);
        
        // Уплотним массив сортировки
        foreach( $sorted as $k => $v ) {
            if( ! array_key_exists( $v, $map ) ) {
                unset( $sorted[ $k ] );
            }
        }
        $sorted = array_values( $sorted );
        
        // Готовим список текущих позиций
        $positions = array();
        foreach ($objects as $i) {
            $positions[] = $i->sortpos;
        }
        
        // Наконец, переписываем позиции
        for( $i = 0; $i < count( $sorted ); $i++ ) {
            $map[ $sorted[ $i ] ]->sortpos = $positions[ $i ];
            $map[ $sorted[ $i ] ]->save();
        }
    }
}

