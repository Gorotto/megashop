<?

//процессор для построения карты сайта
class SiteMapGenerator {

    private $tree;
    private $additional_braches;

    public function __construct($tree = false) {
        if (!$tree) {
            $tree = Pages(array("enabled" => true, "menu" => true, "lvl__gt" => 1))->treeOrder()->tree();
        }

        if ($tree) {
            foreach ($tree as $tree_el) {
                $this->tree[$tree_el->id] = $this->loadMainTreePage($tree_el);
            }
        } else {
            throw new Exception("Нет данных для построения дерева");
        }
    }

    private function loadMainTreePage($page) {
        $page_info = array("title" => $page->title, "sub_pages" => null, "uri" => $page->uri);

        $sub_pages = $page->getChildren();
        if ($sub_pages) {
            foreach ($sub_pages as $sub_page) {
                $page_info["sub_pages"][$sub_page->id] = $this->loadMainTreePage($sub_page);
            }
        }

        return $page_info;
    }

    public function addBranchByAppClassname($data, $appclassname, $add_parent_uri = false) {
        $page = Pages()->get(array("enabled" => true, "type__app_classname" => $appclassname));
        if ($page) {
            $this->addBranch($data, $page->id, $add_parent_uri);
        }
    }

    private function addBranch($pages, $page_id, $add_parent_uri) {
        $this->additional_braches[$page_id] = array("branch_pages" => $pages, "add_parent_uri" => $add_parent_uri);
    }

    public function getTreeAsArray() {
        $all_pages = $this->tree;

        if (count($this->additional_braches) > 0) {
            foreach ($this->additional_braches as $page_id => $branch) {
                $all_pages = $this->searchPageToAddBranch($all_pages, $page_id, $branch);
            }
        }

        return $all_pages;
    }

    private function searchPageToAddBranch(&$tree_pages, $page_id, $branch) {
        foreach ($tree_pages as $tree_page_key => &$tree_page_data) {
            if ($tree_page_key == $page_id) {
                $new_branch = $branch;
                if ($branch['add_parent_uri']) {
                    $this->addParentUri($new_branch['branch_pages'], $tree_page_data['uri']);
                }

                $tree_page_data['sub_pages'] = $new_branch['branch_pages'];
            } else if ($tree_page_data['sub_pages']) {
                $this->searchPageToAddBranch($tree_page_data['sub_pages'], $page_id, $branch);
            }
        }

        return $tree_pages;
    }

    private function addParentUri(&$pages, $parent_uri) {
        foreach ($pages as &$page) {
            $page['uri'] = $parent_uri . $page['uri'];

            if ($page['sub_pages']) {
                foreach ($page['sub_pages'] as $sub_page) {
                    $this->addParentUri($sub_page, $parent_uri);
                }
            }
        }

        return $pages;
    }

}
