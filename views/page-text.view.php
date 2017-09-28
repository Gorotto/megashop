<?php $this->extend('_frame/common') ?>
<?php $this->beginBlock('content'); ?>

<?= new View('_blocks/crumbs'); ?>

<div class="content">
    <h1><?= $this->page->title ?></h1>
    <?= $this->page->text ?>
</div>


<?php $this->endBlock(); ?>
