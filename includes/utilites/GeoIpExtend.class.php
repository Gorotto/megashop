<?
/**
 * Дополнительные функции для класса GeoIp,
 * которые будут относится к определенному проекту.
 * Создан, чтобы лучше отслеживать изменения в основном классе.
 */
class GeoIpExtend {

    static function search_city($search_city) {
        $result = false;

        if (!$search_city) {
            return array();
        }
        
        $query_city = "SELECT net_t_city.name as city_name, net_city.id as city_id FROM `net_city` LEFT JOIN `net_t_city` ON net_city.name_ru=net_t_city.name WHERE net_t_city.name LIKE '{$search_city}%' LIMIT 1";
        $cursor_city = NamiCore::getBackend()->cursor;
        $cursor_city->execute($query_city); 
        $result = $cursor_city->fetchAll();

        if (!$result) {
            $search_city__phrase = mb_substr($search_city, 0, -3, 'utf-8');
            $search_city__phrase = $search_city__phrase ? $search_city__phrase : 0;
            $search_city = mb_strlen($search_city__phrase) < 3 ? $search_city : $search_city__phrase;
            
            if (!$search_city) {
                return array();
            }
            $query_city = "SELECT * FROM `net_city` LEFT JOIN `net_t_city` ON net_city.name_ru=net_t_city.name WHERE net_t_city.name LIKE '%{$search_city}%'";
            $cursor_city = NamiCore::getBackend()->cursor;
            $cursor_city->execute($query_city); 
            $result = $cursor_city->fetchAll();
        }

        return $result;
    }


    /**
    список городов, которые нужно прикрепить к филиалу.
     */
    static function cities_list() {
        $query = "SELECT *, net_city.id as city_id FROM `net_city` LEFT JOIN `net_t_city` ON net_city.name_ru=net_t_city.name WHERE net_t_city.district IN ('Иркутская область', 'Республика Алтай', 'Кемеровская область', 'Томская область', 'Новосибирская область', 'Республика Саха (Якутия)', 'Республика Тыва (Тува)', 'Республика Бурятия', 'Республика Хакасия') OR net_t_city.name IN ('Улан-Удэ', 'Красноярск') ORDER BY district ASC
        ";
        $cursor = NamiCore::getBackend()->cursor;
        $cursor->execute($query); 
        $cities = $cursor->fetchAll();

        return $cities;
    }

    /**
    получение данных о городе, который удалось определить
     */
    function get_detected_city() {
        $cookie = (array)json_decode(urldecode($this->get_cookies()));
        $city = NULL;
        
        $associated_city = $this->_cities_qs->get(array('enabled' => true, 'geo_city' => $this->_gi_city_id));

        if (
                (isset($cookie['city_id']) && $cookie['city_id']) &&
                (isset($cookie['geo_ip_id']) && $cookie['geo_ip_id'])
            ) {
            $cookie['city_id'];
            $cookie['geo_ip_id'];
            $city = $this->_cities_qs->get(array('enabled' => true, 'id' => $cookie['city_id'], 'geo_city' => $cookie['geo_ip_id']));

            // if ($cookie['geo_ip_id'] != $this->_gi_city_id) {
            //     var_dump($cookie['geo_ip_id'], $this->_gi_city_id);
            // }
            
            // "обновление" кук*
            // $this->remove_city_cookie();

        } else {

            $branches_cities = $this->_cities_qs
                ->all();

            foreach ($branches_cities as $b) {
                if (in_array($this->_gi_city_id, $b->attached_cities)) {
                    $city = $b;
                    break;
                }
            }

            if (!$city) {
                $city = $this->default_city();
            }
            
        }

        if ($city) {
            // $this->write_city_cookie($city->id, $city->geo_city);
        }
        
        return $city;
    }
}
?>