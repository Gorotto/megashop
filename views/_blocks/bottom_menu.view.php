<?php $menuItems = MenuItemBottoms(["enabled" => true])->treeOrder()->tree() ?>

<div class="footer__nav">
    <?php foreach ($menuItems as $menuItem) : ?>
        <li class="footer__title"><?= $menuItem->title; ?></li>
        <?php if ($menuSubItems = $menuItem->getChildren()) : ?>   
            <?php foreach ($menuSubItems as $menuSubItem) : ?>
                <li class="footer__subtitle"><a href="<?= $menuSubItem->uri; ?>"><?= $menuSubItem->title; ?></a></li>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endforeach; ?>
</div>