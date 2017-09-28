<?php $this->beginFilter('HtmlFilter::spaceless'); ?>
<!DOCTYPE HTML>
<html>
    <?= new View('_frame/header'); ?>

    <body>
        <div>
            <?= new View('_blocks/top_menu'); ?>
            <?= $this->has('content') ? $this->content : '' ?>
            
            
            <?= new View('_blocks/footer'); ?>
        </div>


        <script src="/static/js/lib/3rdparty/jquery.cookie.js"></script>
        <script src="/static/js/lib/3rdparty/jquery.animateSprite.min.js"></script>
        <script src="/static/js/lib/metaformshandler.js"></script>
        <script src="/static/js/lib/meta.js"></script>
        <script src="/static/js/common.js"></script>


        <?php if (!Builder::developmentMode()): ?>
            <!--YM-->
            <!--GA-->
        <?php endif; ?>

    </body>
</html>

<?php $this->endFilter(); ?>