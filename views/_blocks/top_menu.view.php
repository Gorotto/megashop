<?php $site_pages = Pages(array("enabled" => true, "lvl" => 2))->treeOrder()->all() ?>

<? if ($site_pages): ?>

    <? foreach ($site_pages as $site_page): ?>
        <a href="<?= $site_page->uri ?>" title="<?= $site_page->title ?>">
            <?= $site_page->title ?>
        </a>
    <? endforeach; ?>

<? endif; ?>
