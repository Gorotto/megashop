<?
/**
*   Обертка к phpMorphy, содержит вспомогательные штуки для полнотекстового поиска.
*/
class NamiFulltextProcessor {

	protected $dictionaries;
	protected $options;
	protected $morphy_object_cache = array();
	
	function __construct() {
		require_once(dirname(__FILE__) . '/3rdparty/phpmorphy/src/common.php');

		$this->dictionaries = array(
			'ru' => array(
				'path'	=> dirname(__FILE__) . '/3rdparty/phpmorphy/dicts/morphy-0.3.x-ru_RU-nojo-utf8',
				'lang'	=> 'ru_RU',
			),
		);

		$this->options = array(
			'storage'			=> PHPMORPHY_STORAGE_FILE,
			'predict_by_suffix'	=> true,
			'predict_by_db'		=> true,
			'graminfo_as_text'	=> false,
		);
	}
	
	/**
	*  Получение объекта phpMorphy для указанного языка.
	*  Возвращает объект phpMorphy или null, если для указанного языка нет словарей.
	*/
	function get_morphy_object($language = null) {
        if (!$language) {
			$language = NamiCore::getLanguage();
		}
        
		$lang_key = $language->name;
		
		if (array_key_exists($lang_key, $this->morphy_object_cache)) {
            return $this->morphy_object_cache[$lang_key];
		}
		
		$morphy = null;
		
		if(array_key_exists($lang_key, $this->dictionaries)) {
            $morphy = new phpMorphy($this->dictionaries[$lang_key]['path'],
                $this->dictionaries[$lang_key]['lang'], $this->options);
		}
		
		$this->morphy_object_cache[$lang_key] = $morphy;
		
		return $morphy;
	}

	/**
	*  Получение массива слов из строки текста с мусором - знаками препинания, тегами, кавычками и т.п.
	*  Возвращает массив строк, готовый для отправки в недра phpMorphy.
	*/
    function get_clean_words($text) {
        $text = html_entity_decode($text, ENT_QUOTES, 'utf-8');
        $text = strip_tags($text);
        $text = mb_strtoupper($text);
        $words = preg_split('~[\s,.:;!?"\'()\]\[\-«»„“]~u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $words = array_diff($words, $this->get_stop_words());
		return $words;
	}
	
	/**
	*  Получение базовых форм слов текста.
	*  Возвращает массив строк.
	*/
	function get_base_forms($text, $language = null) {
        $clean_words = $this->get_clean_words($text);
        
        $morphy = $this->get_morphy_object($language);
        
		if(!$morphy) {
            return $clean_words;
		}
        
		$form_list = $morphy->getBaseForm($clean_words);
		
		$word_map = array();
		
		foreach ($form_list as $source_word => $forms) {
            if ($forms) {
                foreach ($forms as $word) {
                    $word_map[$word] = true;
                }
            } else {
                $word_map[$source_word] = true;
            }
		}
		
		$words = array_keys($word_map);

		return $words;
	}
	
    /**
    *   Получение всех форм слов текста.
    *   Возвращает массив массивов строк (в отличие от get_base_forms, которая возвращает простой массив).
    *   Каждому массиву в результате соответствует одно слово в исходном тексте.
    */
	function get_all_forms($text, $language = null) {
        $clean_words = $this->get_clean_words($text);

        $morphy = $this->get_morphy_object($language);
        
		if (!$morphy) {
            $words = array();
            foreach ($clean_words as $word) {
                $words[$word] = array($word);
            }
            return $words;
		}

		$form_list = $morphy->getAllForms($clean_words);
		
		$word_lists = array();
		
		foreach ($form_list as $source_word => $forms) {
            if ($forms) {
                $word_lists[$source_word] = $forms;
            } else {
                $word_lists[$source_word] = array($source_word);
            }
		}

        return $word_lists;
	}
    
    /**
    *   Возвращает массив стоп-слов, все слова в верхнем регистре.
    */
    function get_stop_words() {
        static $stop_words = array(
            'A',
            'ABOUT ',
            'AFAIK',
            'AFAIR',
            'AFTER ',
            'AGO ',
            'ALL ',
            'ALMOST ',
            'ALONG ',
            'ALOT',
            'ALSO ',
            'AM ',
            'AN ',
            'AND',
            'AND ',
            'ANSWER ',
            'ANY ',
            'ANYBODY ',
            'ANYBODYS',
            'ANYWHERE ',
            'ARE ',
            'ARENT ',
            'AROUND ',
            'AS ',
            'ASK',
            'ASKD',
            'AT ',
            'B',
            'BAD ',
            'BE ',
            'BECAUSE ',
            'BEEN ',
            'BEFORE ',
            'BEING ',
            'BEST ',
            'BETTER ',
            'BETWEEN ',
            'BIG ',
            'BTW ',
            'BUT ',
            'BY ',
            'C',
            'CAN ',
            'CANT ',
            'COME ',
            'COULD ',
            'COULDNT ',
            'DAY ',
            'DAYS',
            'DID ',
            'DIDNT ',
            'DO ',
            'DOES ',
            'DOESNT ',
            'DONT ',
            'DOWN ',
            'E',
            'EACH ',
            'EEK',
            'EITHER ',
            'ELSE ',
            'ETC ',
            'EVEN ',
            'EVER ',
            'EVERY ',
            'EVERYBODY ',
            'EVERYBODYS',
            'EVERYONE ',
            'F',
            'FAR ',
            'FIND ',
            'FOR ',
            'FOUND ',
            'FROM ',
            'FTP',
            'G',
            'GET ',
            'GO ',
            'GOING ',
            'GONE ',
            'GOOD ',
            'GOT ',
            'GOTTEN ',
            'GRIN',
            'H',
            'HAD ',
            'HAS ',
            'HAVE ',
            'HAVENT ',
            'HAVING ',
            'HER ',
            'HERE ',
            'HERS ',
            'HIM ',
            'HIS ',
            'HOME ',
            'HOW ',
            'HOWS',
            'HREF ',
            'HTTP',
            'I',
            'I ',
            'IF ',
            'IIRC',
            'IMHO',
            'IN ',
            'INI ',
            'INTO ',
            'IS ',
            'ISNT ',
            'IT ',
            'ITS',
            'ITS ',
            'IVE',
            'J',
            'JUST',
            'K',
            'KNOW ',
            'L',
            'LARGE ',
            'LESS ',
            'LIKE ',
            'LIKED',
            'LITTLE ',
            'LOL',
            'LOOK ',
            'LOOKED',
            'LOOKING',
            'LOOKING ',
            'LOT ',
            'M',
            'MANY ',
            'MAYBE ',
            'ME ',
            'MORE ',
            'MOST ',
            'MRGREEN',
            'MUCH ',
            'MUST ',
            'MUSTNT',
            'MY ',
            'NEAR ',
            'NEED ',
            'NEVER ',
            'NEW ',
            'NEWS ',
            'NO ',
            'NONE ',
            'NOT',
            'NOT ',
            'NOTHING ',
            'NOW ',
            'O',
            'OF ',
            'OFF ',
            'OFTEN ',
            'OLD ',
            'ON ',
            'ONCE ',
            'ONLY ',
            'OOPS',
            'OR',
            'OR ',
            'OTHER ',
            'OUR ',
            'OURS ',
            'OUT ',
            'OVER ',
            'P',
            'PAGE ',
            'PLEASE ',
            'PUT ',
            'Q',
            'QUESTION ',
            'QUESTIONED',
            'QUESTIONS',
            'QUOTE',
            'R',
            'RATHER ',
            'RAZZ',
            'REALLY ',
            'RECENT ',
            'ROLL',
            'ROTF',
            'ROTFLMAO',
            'S',
            'SAID',
            'SAW',
            'SAY',
            'SAYS',
            'SEE',
            'SEES',
            'SHE ',
            'SHOULD ',
            'SITES ',
            'SMALL ',
            'SMILE',
            'SO ',
            'SOME ',
            'SOMETHING ',
            'SOMETIME ',
            'SOMEWHERE ',
            'SOON ',
            'TAKE ',
            'THAN ',
            'THANK ',
            'THAT ',
            'THATD ',
            'THATS ',
            'THE ',
            'THEIR ',
            'THEIRS',
            'THEIRS ',
            'THEM ',
            'THEN ',
            'THERE ',
            'THERES',
            'THESE ',
            'THEY ',
            'THEYD',
            'THEYLL',
            'THEYRE',
            'THIS ',
            'THOSE ',
            'THOUGH ',
            'THROUGH ',
            'THUS ',
            'TIME ',
            'TIMES ',
            'TO ',
            'TOO ',
            'TRUE ',
            'TWISTED',
            'U',
            'UNDER ',
            'UNTIL ',
            'UNTRUE ',
            'UP ',
            'UPON ',
            'USE ',
            'USERS ',
            'V',
            'VERSION ',
            'VERY ',
            'VIA ',
            'W',
            'WANT ',
            'WAS ',
            'WAY ',
            'WE',
            'WEB',
            'WELL',
            'WENT',
            'WERE ',
            'WERENT',
            'WHAT ',
            'WHEN ',
            'WHERE ',
            'WHICH ',
            'WHO ',
            'WHOM ',
            'WHOSE ',
            'WHY ',
            'WIDE ',
            'WILL ',
            'WINK',
            'WITH ',
            'WITHIN ',
            'WITHOUT ',
            'WONT',
            'WORLD ',
            'WORSE ',
            'WORST ',
            'WOULD',
            'WROTE',
            'WWW',
            'WWW ',
            'X',
            'Y',
            'YES ',
            'YET ',
            'YMMV',
            'YOU ',
            'YOUD',
            'YOULL',
            'YOUR ',
            'YOURE',
            'YOURS',
            'Z',
            'А',
            'Б',
            'БУДЕТ',
            'БУДИТ',
            'БУЕДТ',
            'БФТЬ',
            'БЫ',
            'БЫЛ',
            'БЫЛА',
            'БЫЛИ',
            'БЫЛО',
            'БЫТЬ',
            'В',
            'ВАМ',
            'ВАС',
            'ВЕСЬ',
            'ВО',
            'ВОТ',
            'ВСЕ',
            'ВСЕГО',
            'ВСЕХ',
            'ВЫ',
            'Г',
            'Д',
            'ДА',
            'ДЛЯ',
            'ДНИ',
            'ДНЯМИ',
            'ДО',
            'ДЫК',
            'ДЯЛ',
            'Е',
            'Ё',
            'ЕВСЬ',
            'ЕГО',
            'ЕЕ',
            'ЕЙ',
            'ЕСЛИ',
            'ЕСТЬ',
            'ЕЩЕ',
            'Ж',
            'ЖЕ',
            'З',
            'ЗА',
            'И',
            'ИБО',
            'ИЗ',
            'ИЛИ',
            'ИМ',
            'ИМХО',
            'ИХ',
            'Й',
            'К',
            'КАК',
            'КАКЖЕ',
            'КО',
            'КОГДА',
            'КООТРЫЙ',
            'КОТОРАЯ',
            'КОТОРЙО',
            'КОТОРОЙ',
            'КОТОРЫЙ',
            'КОТОРЫХ',
            'КТО',
            'Л',
            'М',
            'МЕГАЛОЛ',
            'МЕНЯ',
            'МЛЯ',
            'МНЕ',
            'МНОЙ',
            'МОГЛИ',
            'МОГУ',
            'МОГУТ',
            'МОЖЕТ',
            'МОЧЬ',
            'МЫ',
            'Н',
            'НА',
            'НАХ',
            'НАШ',
            'НЕ',
            'НЕГО',
            'НЕЕ',
            'НЕТ',
            'НИХ',
            'НО',
            'О',
            'ОБ',
            'ОМЧЬ',
            'ОН',
            'ОНА',
            'ОНИ',
            'ОНО',
            'ОТ',
            'ОТВЕТ',
            'П',
            'ПО',
            'ПРИ',
            'Р',
            'С',
            'СВЙО',
            'СВОЕ',
            'СВОЙ',
            'СВОЯ',
            'СЕБЯ',
            'СОБОЙ',
            'Т',
            'ТАГДА',
            'ТАК',
            'ТАКЙО',
            'ТАКОЙ',
            'ТЕ',
            'ТЕБЯ',
            'ТЕМ',
            'ТО',
            'ТОБОЙ',
            'ТОГДА',
            'ТОГО',
            'ТОЙ',
            'ТОЛЬКО',
            'ТОМ',
            'ТОЬЛКО',
            'ТЫ',
            'У',
            'УЖЕ',
            'УПС',
            'Ф',
            'Х',
            'ХОРОШЕ',
            'Ц',
            'Ч',
            'ЧЕГО',
            'ЧЕМ',
            'ЧТО',
            'ЧТОБЫ',
            'Ш',
            'ШТА',
            'Щ',
            'Ъ',
            'Ы',
            'ЫБТЬ',
            'Ь',
            'Э',
            'ЭТА',
            'ЭТИ',
            'ЭТО',
            'ЭТОТ',
            'Ю',
            'Я',
        );

        return $stop_words;
    }
}