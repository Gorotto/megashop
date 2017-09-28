<?

/*
 * Корзина.
 *
 * для авторизованных пользователей данные складываются в базу
 * для остальных - в куки
 */

class Cart {

    private static $instance = null;
    private $cookie_name = 'user_cart';
    private $cookie_expire = 0;
    private $items = array();
    private $items_total_price = 0;
    private $items_total_qty = 0;
    private $items_ids = array();
    private $optional_fields = array();

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Cart();

            self::$instance->load_data();
        }
        return self::$instance;
    }

    //pattern singleton
    private function __clone() {

    }

    private function __construct() {

    }

    public function get_items() {
        return $this->items;
    }

    public function get_items_ids() {
        return $this->items_ids;
    }

    public function get_total_qty() {
        return $this->items_total_qty;
    }

    public function get_total_price() {
        return $this->items_total_price;
    }

    private function load_data() {
        $this->cookie_expire = time() + 60 * 60 * 24 * 30;

        if (!$this->items) {
            $cart_data = null;

            if (SiteSession::getInstance()->isAuthorized()) {
                $user = SiteSession::getInstance()->getUser();

                if (!property_exists($user, 'cart_data')) {
                    SiteSession::getInstance()->reloadData();
                    $user = SiteSession::getInstance()->getUser();
                }

                if ($user->cart_data) {
                    $cart_data = json_decode(stripslashes(gzinflate(base64_decode($user->cart_data))), true);
                }
            } else if (array_key_exists($this->cookie_name, $_COOKIE) && $_COOKIE[$this->cookie_name]) {
                $cart_data = json_decode(stripslashes(gzinflate(base64_decode($_COOKIE[$this->cookie_name]))), true);
            }

            if ($cart_data) {
                $this->items = $cart_data;

                $ids = array();
                foreach ($this->items as $item) {
                    if (isset($item['id'])) {
                        $ids[] = $item['id'];
                    }

                    if (isset($item['price'])) {
                        $this->items_total_price += $item['price'] * $item['qty'];
                    }

                    if (isset($item['qty'])) {
                        $this->items_total_qty += $item['qty'];
                    }
                }

                $this->items_ids = $ids;
            }
        }
    }

    private function clear_data() {
        if (SiteSession::getInstance()->isAuthorized()) {
            $user = SiteSession::getInstance()->getUser();
            $user->cart_data = null;
            $user->save();
            SiteSession::getInstance()->reloadData();
        } else {
            unset($_COOKIE[$this->cookie_name]);
            setcookie($this->cookie_name, "", $this->cookie_expire, '/');
        }

        return true;
    }

    private function save_data() {
        $this->items_total_price = 0;
        $this->items_total_qty = 0;
        $this->items_ids = array();

        $ids = array();
        if ($this->items) {
            foreach ($this->items as $item) {
                if (isset($item['id'])) {
                    $ids[] = $item['id'];
                }

                if (isset($item['price'])) {
                    $this->items_total_price += $item['price'] * $item['qty'];
                }

                if (isset($item['qty'])) {
                    $this->items_total_qty += $item['qty'];
                }
            }
        }

        $this->items_ids = $ids;

        $data = json_encode($this->items);
        $data = addslashes($data);
        $data = gzdeflate($data);
        $data = base64_encode($data);


        if (SiteSession::getInstance()->isAuthorized()) {
            $user = SiteSession::getInstance()->getUser();
            $user->cart_data = $data;
            $user->save();
        } else {
            setcookie($this->cookie_name, $data, $this->cookie_expire, '/');
        }

        return true;
    }

    private function format_item($item) {
        $validated_item = false;

        $validated_item['id'] = (int) $item['id'];
        $validated_item['qty'] = (int) $item['qty'];
        $validated_item['price'] = (float) $item['price'];

        foreach ($this->optional_fields as $field_name) {
            if (isset($item[$field_name])) {
                $validated_item[$field_name] = $item[$field_name];
            }
        }

        return $validated_item;
    }

    public function add_item($item_new_data) {
        if (!isset($item_new_data['id'])) {
            return false;
        }

        $item_new_data = $this->format_item($item_new_data);

        if (!in_array($item_new_data['id'], $this->items_ids)) {
            $this->items[] = $item_new_data;
        } else {
            foreach ($this->items as $item_num => $item) {
                if ($item['id'] == $item_new_data['id']) {
                    $item_new_data['qty'] = $item_new_data['qty'] + $this->items[$item_num]['qty'];
                    $this->items[$item_num] = $item_new_data;
                }
            }
        }

        $this->save_data();
        return true;
    }

    public function remove_item($item_id) {
        $new_items = array();

        foreach ($this->items as $item) {
            if ($item['id'] != $item_id) {
                $new_items[] = $item;
            }
        }

        $this->items = $new_items;

        $this->save_data();
        return true;
    }

    public function update_many($items_new_data) {
        foreach ($items_new_data as $item_new_data) {

            $item_new_data = $this->format_item($item_new_data);

            if (!in_array($item_new_data['id'], $this->items_ids)) {
                $this->items[] = $item_new_data;
            } else {
                foreach ($this->items as $item_num => $item) {
                    if ($item['id'] == $item_new_data['id']) {
                        $item_new_data['qty'] = $item_new_data['qty'];
                        $this->items[$item_num] = $item_new_data;
                    }
                }
            }
        }

        $this->save_data();
        return true;
    }

    public function update_item($item_new_data) {
        if (!isset($item_new_data['id'])) {
            return false;
        }

        $item_new_data = $this->format_item($item_new_data);

        foreach ($this->items as $item_num => $item) {
            if ($item['id'] == $item_new_data['id']) {
                $this->items[$item_num] = $item_new_data;
            }
        }

        $this->save_data();
        return true;
    }

    public function clear_cart() {
        $this->items = null;
        $this->clear_data();

        $this->save_data();
        return true;
    }

    public function in_cart($item_id) {
        return in_array($item_id, $this->items_ids);
    }

    public function update_cart() {
        $items_ = array();

        foreach ($this->items as $item) {
            $entry = CatalogEntries()->get(array(
                "id" => $item['id'],
                "enabled" => true,
                "category__enabled" => true,
            ));

            if ($entry) {
                $items_[] = array(
                    "id" => $entry->id,
                    "qty" => $item['qty'],
                    "price" => $entry->total_price
                );
            }
        }

        $this->items = $items_;
        $this->save_data();
    }
}
