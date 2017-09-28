<?
$is_first = true;

$crumb_pages = array();
$crumb_pages[] = array("title" => "Главная", "uri" => "/");
$parent_pages = Pages()->filterParents($this->page)->filterLevel(2, 0)->order('lvl')->values(array('title', 'uri'));

$crumb_pages = array_merge($crumb_pages, $parent_pages);


if ($this->has("crumbs_pages") && $this->crumbs_pages) {
    foreach ($this->crumbs_pages as $page) {
        $crumb_pages[] = $page;
    }
}
?>

<nav class="crumbs">
    <div class="crumbs__wrap">
        <div class="crumbs__row">
            <div class="crumbs__items">

                <? foreach ($crumb_pages as $crumb_page): ?>
                    <a href="<?= $crumb_page['uri'] ?>" class="crumbs__item" title="<?= strip_tags($crumb_page['title']) ?>">
                        <span class="crumbs__item-title"><?= strip_tags($crumb_page['title']) ?></span>
                    </a>
                <? endforeach; ?>

                <a title="<?= strip_tags($this->page->title) ?>" class="crumbs__item_current">
                    <span class="crumbs__item-title">
                        <?= strip_tags($this->page->title) ?>
                    </span>
                </a>

            </div>
        </div>
    </div>
</nav>
