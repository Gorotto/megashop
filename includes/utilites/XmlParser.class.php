<?
/**
    SAX-парсер xml файлов.

    Из такой стркутуры документа:
    
    <index>
        <categories>
            <item id="1">
                <title>Some item</title>
                <item id="2">
                    <title>Another i</title>
                </item>
            </item>
        </categories>
    </index>

    парсер последовательно создаст два массива:

    $data = array('section' => categories, 'depth' => 3, 'id' => 1, 'title' => Some item)
    $data = array('section' => categories, 'depth' => 4, 'id' => 2, 'parent' => 1, 'title' => Another item)

    и для каждого из них вызовет метод объекта класса ParserDataWorker work($data);
*/
class XmlParser {
    protected $sections = array('entries'); // корневые разделы файла выгрузки
    protected $item_tagname = 'item'; // тег отдельного элемент
    protected $filepath; // путь до файла
    protected $data_worker; // ParserDataWorker
    
    private $depth = 0; // глубина
    private $current_tag_name; // текущий открытый tag 
    private $current_section; // текущая ветка, например categories или items
    private $items = array(); // стек с айтемами
    
    function __construct() {
        # pass
    }
    
    public function parse($params) {
        $this->filepath = $params['filepath'];
        $this->data_worker = $params['data_worker'];
        $this->sections = $params['sections'];
        $this->item_tagname = $params['item_tagname'];

        if (!preg_match('~^(.+)\.xml$~', $this->filepath)) {
            throw new ParserException("Формат файла должен быть .xml");
        }

        $xml_parser = xml_parser_create();
        xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_element_handler($xml_parser, array(&$this, 'startElement'), array(&$this, 'endElement'));
        xml_set_character_data_handler($xml_parser, array(&$this, 'stringElement'));
    
        $fp = @fopen($this->filepath, "r");
        if (!$fp) {
            throw new ParserException("Не удается открыть файл {$this->filepath}");
        }       
        while ($data = fread($fp, 8192)) {
            if (!xml_parse($xml_parser, $data, feof($fp))) {
                throw new ParserException("Ошибка в ".xml_get_current_line_number($xml_parser)." файла xml: ". xml_error_string(xml_get_error_code($xml_parser)));
            }
        }
        xml_parser_free($xml_parser);
    }
    
    private function stringElement($parser, $data) {
        if($data && $this->current_tag_name) {
            if( array_key_exists($this->current_tag_name, $this->items[count($this->items)-1]) ) {
                $this->items[count($this->items)-1][$this->current_tag_name] .= $data;
            } else {
                $this->items[count($this->items)-1][$this->current_tag_name] = $data;
            }        
            if(array_key_exists('done',$this->items[count($this->items)-1])){
                # если мы уже обрабаывали этот элемент,
                # снимаем отметку 'done', поскольку появились новые данные
                # и теперь обработчик endElement вызовет db_job еще раз
                unset($this->items[count($this->items)-1]['done']);
            }
        }
    }
    
    private function startElement($parser, $name, $attrs) {
        $this->depth++;       
        // смотрим на tag $name        
        if ($name == 'index') {
            # pass
        } elseif (in_array($name, $this->sections)) {
            # записываем $this->current_section, чтобы знать дальше, что делать с item при вставке
            $this->current_section  = $name;
        } elseif ($name == $this->item_tagname) {
            $this->items[] = array();
            # заполняем последний элемент стека
            $this->items[count($this->items)-1]['section'] = $this->current_section;
            $this->items[count($this->items)-1]['depth'] = $this->depth;
            foreach($attrs as $attr => $value) {
                $this->items[count($this->items)-1][$attr] = $value;
            }
            if(isset($this->items[count($this->items)-2]['depth']) && $this->items[count($this->items)-2]['depth'] < $this->items[count($this->items)-1]['depth']) {
                # если в стеке есть еще что-то кроме нашего элемента, и у этого чего-то глубина меньше нашей,
                # значит это что-то - родитель нашего элемента (поскольку все закрытые элементы из стека удаляются)
                # Проверим, не обрабатывали ли мы его ранее, если нет, отдаем его функции работы с бд
                $this->items[count($this->items)-1]['parent'] = $this->items[count($this->items)-2]['id'];
                if(!array_key_exists('done', $this->items[count($this->items)-2])){
                    $this->data_worker->work($this->items[count($this->items)-2]);
                    $this->items[count($this->items)-2]['done'] = true;
                }
            }
        } else {
            $this->current_tag_name = $name;
        }
    }
    
    private function endElement($parser, $name) {
        $this->depth--;
        if ($name == 'index') {
            #break 
        } elseif (in_array($name, $this->sections)) {
            $this->current_section = '';
        } elseif ($name == $this->item_tagname) {
            if (!array_key_exists('done', $this->items[count($this->items)-1])) {
                # проверяем, не обрабатывали ли мы этот элемент ранее
                $this->data_worker->work($this->items[count($this->items)-1]);
            }
            array_pop($this->items);
        } else {
            $this->current_tag_name = '';
        }
    }
}

class ParserException extends Exception {

}