<?php

/**
 * NamiPaginator ver. 2.1
 * Постраничный навигатор по NamiQuerySet-ам или массиву
 * Использует шаблон, переданный в конструкторе.
 * Имеет магию приведения к строке для вывода HTML-кода.
 *
 * fix: доработать вывод в виде get параметров. просто не успел.
 *
 *  |Array|
 *      |
 *       ----|start_page|
 *      |       |
 *      |        -----|uri|
 *      |
 *       ----|prev_page|
 *      |       |
 *      |        -----|uri|
 *      |
 *       ----|links|
 *      |       |
 *      |        -----|Array|
 *      |                |
 *      |                 -----|title|
 *      |                |
 *      |                 -----|uri|
 *      |
 *       ----|next_page|
 *      |       |
 *      |        -----|uri|
 *      |
 *      -----|last_page|
 *              |
 *               -----|uri|
 *
 */
class NamiPaginator {

    public function set_maxlinks($maxlinks) {
        $this->maxlinks = $maxlinks;
    }

    private $maxlinks = 7;              // Максимальное количество выводимых на страницу ссылок. меньше 4 лучше не указывать
    private $el_per_page;               // количесто элементов на странице
    private $objects;                   // Объекты. массив или QuerySet.
    private $objects_count;             // Количество объектов всего
    private $view;                      // Шаблон вывода постраничной навигации
    private $uri_template;              // uri шаблон страницы, %{page} заменяется на номер страницы
    private $current_page;              // текущая страница паджинатора
    private $page_count;                // всего страниц паджинатора
    private $all_pages;                 // массив страниц
    private $as_GET_param;              // Вывод ссылок на страницу как GET параматр
    private $page_separator = array(// Разделитель страниц
        'title' => "…",
        'uri' => "",
        'is_current' => false,
        'is_separator' => true,
        'visible' => true
    );

    /**
     * Конструктор
     * @param NamiQuerySet $query query запрос либо массив
     * @param type $view путь к view
     * @param type $size количество выводимых элементов на странице
     * @param type $as_GET_param выводить путь к страницам как GET параметр
     * @throws Exception
     */
    function __construct($query, $view, $el_per_page, $as_GET_param = false) {
        $this->view = $view;
        $this->el_per_page = $el_per_page;
        $this->as_GET_param = $as_GET_param;

        $this->set_uri_template();
        $this->set_objects_count($query);
        $this->page_count = ceil($this->objects_count / $this->el_per_page);
        $this->set_current_page();
        $this->set_objects($query);
    }

    /**
     * Формирует шаблон урл.
     */
    private function set_uri_template() {
        $uri = $_SERVER['REQUEST_URI'];
        if (!preg_match("/page\d+/", $uri)) {
            $uri_part = @parse_url($uri);

            if (mb_substr($uri_part['path'], -1) == "/") {
                $uri = $uri_part['path'] . "page1/";
            } else {
                $uri = $uri_part['path'] . "/page1/";
            }

            if (isset($uri_part["query"])) {
                $uri .= "?" . $uri_part['query'];
            }
        }

        $this->uri_template = preg_replace("/page\d+/", "page%{page}", $uri);
    }

    /**
     * Вычисляем текущюю страницу
     */
    private function set_current_page() {
        $this->current_page = 1;
        if (preg_match('/page(\d+)$/', Meta::getUriPath(), $m) && (int) $m[1] > 0) {
            $this->current_page = (int) $m[1];
        }

        if ($this->objects_count > 0) {
            //если хитрый юзер указал страницу очень большую - кидать на последнюю
            $max_pages = (int) $this->page_count;
            if ($max_pages < $this->current_page) {
                $this->current_page = $max_pages;
            }
        }
    }

    /**
     * Записываем текущий queryset или массив
     */
    private function set_objects_count($query) {
        if ($query instanceof NamiQuerySet) {
            $this->objects_count = $query->count();
        } elseif (is_array($query)) {
            $this->objects_count = count($query);
        } else {
            throw new Exception("Invalid paginator query");
        }
    }

    /**
     * Заполняем поле objects
     * перед этим нужно просчитать количество страниц и текущую страницу
     */
    private function set_objects($query) {
        if ($query instanceof NamiQuerySet) {
            $this->objects = $query->limit($this->el_per_page, $this->el_per_page * ( $this->current_page - 1 ));
        } elseif (is_array($query)) {
            $this->objects = array_slice($query, $this->el_per_page * ( $this->current_page - 1 ), $this->el_per_page);
        } else {
            throw new Exception("Invalid paginator query");
        }
    }

    public function fetch() {
        $pages = array();
        if ($this->objects_count > $this->el_per_page) {
            //---- first page link --------------------------------------------
            $pages['start_page'] = array(
                'uri' => str_replace("/page%{page}", "", $this->uri_template),
                'title' => 'start page',
                'is_current' => false,
                'is_separator' => false
            );
            if ($this->current_page == 1) {
                $pages['start_page']['is_current'] = true;
            }

            //---- prev page link --------------------------------------------
            $prev_is_current = false;
            if ($this->current_page == 2) {
                $prev_uri = str_replace("/page%{page}", "", $this->uri_template);
            } else if ($this->current_page == 1) {
                $prev_uri = str_replace("/page%{page}", "", $this->uri_template);
                $prev_is_current = true;
            } else {
                $prev_uri = NamiUtilities::array_printf($this->uri_template, array('page' => $this->current_page - 1));
            }

            $pages['prev'] = array(
                'uri' => $prev_uri,
                'title' => 'prev',
                'is_current' => $prev_is_current,
                'is_separator' => false
            );

            //----- links -----------------------------------------------------
            $pages['links'] = array();

            //напечатать все ссылки нельзя, т.к. максимально доступно меньше
            if ($this->maxlinks < $this->page_count) {

                $print_links = array();
                $availible_links = $this->maxlinks;

                //заполняем временный массив
                for ($i = 1; $i <= $this->page_count; $i++) {
                    $page_uri = NamiUtilities::array_printf($this->uri_template, array('page' => $i));
                    $page_is_current = false;
                    if ($i == $this->current_page) {
                        $page_is_current = true;
                    }
                    if ($i == 1) {
                        $page_uri = str_replace("/page%{page}", "", $this->uri_template);
                    }
                    $print_links[$i] = array(
                        'title' => $i,
                        'uri' => $page_uri,
                        'is_current' => $page_is_current,
                        'is_separator' => false,
                        'visible' => false
                    );
                }

                //отмечаем первую и последнюю страницу
                $print_links[1]['visible'] = true;
                $print_links[$this->page_count]['visible'] = true;
                $availible_links -= 2;

                //оставшиеся ссылки добавляем после текущей
                $availible_links_to_next = ceil($availible_links / 2);
                for ($i = $this->current_page; ($availible_links_to_next > 0 && $i <= $this->page_count); $i++) {
                    if (!$print_links[$i]['visible']) {
                        $print_links[$i]['visible'] = true;
                        $availible_links_to_next--;
                        $availible_links--;
                    }
                }

                //и до текущей
                $availible_links_to_prev = $availible_links;
                if ($availible_links_to_prev > 0) {
                    for ($i = $this->current_page; ($i > 0 && $availible_links_to_prev > 0); $i--) {
                        if (!$print_links[$i]['visible']) {
                            $print_links[$i]['visible'] = true;
                            $availible_links_to_prev--;
                            $availible_links--;
                        }
                    }
                }

                //и если еще осталось - добавляем после
                if ($availible_links > 0) {
                    for ($i = $this->current_page; ($availible_links > 0 && $i <= $this->page_count); $i++) {
                        if (!$print_links[$i]['visible']) {
                            $print_links[$i]['visible'] = true;
                            $availible_links--;
                        }
                    }
                }


                //set separators
                $is_separator = false;
                for ($i = 1; $i < count($print_links) + 1; $i++) {
                    if ($print_links[$i]['visible'] == false) {
                        if (!$is_separator) {
                            $print_links[$i] = $this->page_separator;
                            $is_separator = true;
                        }
                    } else {
                        $is_separator = false;
                    }
                }

                for ($i = 1; $i < count($print_links) + 1; $i++) {
                    if ($print_links[$i]['visible']) {
                        $pages['links'][] = $print_links[$i];
                    }
                }
            } else {
                for ($i = 1; $i <= ($this->page_count); $i++) {
                    if ($i == 1) {
                        $page_uri = str_replace("/page%{page}", "", $this->uri_template);
                    } else {
                        $page_uri = NamiUtilities::array_printf($this->uri_template, array('page' => $i));
                    }
                    if ($this->current_page == $i) {
                        $page_is_current = true;
                    } else {
                        $page_is_current = false;
                    }

                    $pages['links'][] = array(
                        'title' => $i,
                        'uri' => $page_uri,
                        'is_current' => $page_is_current,
                        'is_separator' => false
                    );
                }
            }

            //---- next page link --------------------------------------------
            if ($this->current_page == $this->page_count) {
                $next_uri = NamiUtilities::array_printf($this->uri_template, array('page' => $this->current_page));
                $next_is_current = true;
            } else {
                $next_uri = NamiUtilities::array_printf($this->uri_template, array('page' => $this->current_page + 1));
                $next_is_current = false;
            }
            $pages['next'] = array(
                'uri' => $next_uri,
                'title' => 'next',
                'is_current' => $next_is_current,
                'is_separator' => false
            );

            //---- last page link --------------------------------------------
            $pages['last_page'] = array(
                'uri' => NamiUtilities::array_printf($this->uri_template, array('page' => $this->page_count)),
                'title' => 'last_page',
                'is_current' => false,
                'is_separator' => false
            );
            if ($this->current_page == $this->page_count) {
                $pages['last_page']['is_current'] = true;
            }
        }

        return new View($this->view, array('pages' => $pages));
    }

    /**
      Приведение объекта к строке — заменим его вызовом fetch(), весьма пригодится.
     */
    public function __toString() {
        return (string) $this->fetch();
    }

    function __get($name) {
        if (in_array($name, array('page_count', 'objects', 'el_per_page'))) {
            return $this->$name;
        } else {
            return false;
        }
    }

}