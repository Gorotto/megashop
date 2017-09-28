<?
$links = array(
    array(
        "title" => "Страницы",
        "view_names" => array(
            "modules/PagesModule/pages",
        ),
        "link" => $this->uri . "/",
    )
);
?>


<div class="uk-grid">
    <div class="uk-width-1-1">
        <ul class="uk-subnav uk-subnav-pill">

            <? foreach ($links as $item): ?>
                <?
                $is_cur = false;

                if ($this->has("view_name")) {
                    if (in_array($this->view_name, $item['view_names'])) {
                        $is_cur = true;
                    }
                }
                ?>

                <li class="<? if ($is_cur): ?>uk-active<? endif; ?>">
                    <a href="<?= $item['link'] ?>">
                        <?= $item['title'] ?>
                    </a>
                </li>
            <? endforeach; ?>


            <li data-uk-dropdown="{mode:'click'}" class="<? if ($this->view_name == 'menu'): ?>uk-active<? endif; ?>">
                <a href="">
                    Меню
                    <i class="uk-icon-caret-down"></i>
                </a>

                <div class="uk-dropdown uk-dropdown-small">
                    <ul class="uk-nav uk-nav-dropdown">
                        <? foreach (MenuItem::$positions as $position => $title): ?>
                            <li>
                                <a href="<?= $this->uri . "/menu/?position=" ?><?= $position ?>">
                                    <?= $title ?>
                                </a>
                            </li>
                        <? endforeach; ?>
                    </ul>
                </div>
            </li>


            <? if (CmsApplication::is_develop_mode()): ?>
                <li class="<? if ($this->view_name == 'types'): ?>uk-active<? endif; ?>">
                    <a href="<?= $this->uri ?>/page_types">
                        Типы страниц
                    </a>
                </li>
            <? endif; ?>
        </ul>
    </div>
</div>
