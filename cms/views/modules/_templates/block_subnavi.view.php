<?
$links = array(
    array(
        "title" => "Заведения",
        "view_names" => array(
            "modules/EatPlacesModule/places",
        ),
        "link" => $this->uri . "/",
    ),
    array(
        "title" => "Группы филиалов",
        "view_names" => array(
            "modules/EatPlacesModule/groups",
        ),
        "link" => $this->uri . "/groups/",
    ),
    array(
        "title" => "Страны и города",
        "view_names" => array(
            "modules/EatPlacesModule/countries",
            "modules/EatPlacesModule/cities",
        ),
        "link" => $this->uri . "/countries/",
    ),
    array(
        "title" => "Усулги",
        "view_names" => array(
            "modules/EatPlacesModule/services",
        ),
        "link" => $this->uri . "/services/",
    ),
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

        </ul>
    </div>
</div>
