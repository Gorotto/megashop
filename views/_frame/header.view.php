<head>
    <meta charset="UTF-8">

    <?php if ($this->page->meta_keywords): ?><meta name="keywords" content="<?= htmlspecialchars(strip_tags($this->page->meta_keywords)) ?>"><?php endif ?>
    <?php if ($this->page->meta_description): ?><meta name="description" content="<?= htmlspecialchars(strip_tags($this->page->meta_description)) ?>"><?php endif ?>

    <link rel="canonical" href="<?= "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] ?>">

    <?php if ($this->has("ogtags")): ?>
        <?= $this->ogtags ?>
    <?php else: ?>
        <meta property="og:url" content="<?= "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] ?>">
        <meta property="og:title" content="<?= Config::get('common.site_title') ?>">
        <meta property="og:description" content="<?= $this->page->meta_description ? htmlspecialchars(strip_tags($this->page->meta_description)) : $title ?>">
        <meta property="og:image" content="/static/i/share_logo.png">
        <meta property="og:site_name" content="<?= htmlspecialchars(strip_tags(Config::get('common.site_title'))) ?>">
        <meta property="og:type" content="article">
        <meta property="og:locale" content="ru_RU" />
        <meta property="og:image:url" content="http://<?= $_SERVER["HTTP_HOST"] ?>/static/i/favicons/600x315.png">
        <link rel="image_src" href="http://<?= $_SERVER["HTTP_HOST"] ?>/static/i/favicons/600x315.png" />
<!--        <meta property="og:image:type" content="image/png">
        <meta property="og:image:width" content="600">
        <meta property="og:image:height" content="400">-->
    <?php endif; ?>

    <?php 
    if ($this->page->meta_title) {
        $title = $this->page->meta_title;
    } else {
        $all_pages = array();
        $parent_pages = Pages()->filterParents($this->page)->filterLevel(2, 0)->order('lvl')->only('title')->all();
        foreach ($parent_pages as $parent_page) {
            $all_pages[] = $parent_page->title;
        }
        if ($this->page->title) {
            $all_pages[] = $this->page->title;
        }
        array_push($all_pages, Config::get('common.site_title'));
        $title = join(' — ', $all_pages);
    }
    ?>
    <title><?= htmlspecialchars(strip_tags($title)) ?></title>

    <link href="/static/css/common.css" rel="stylesheet">
<!--    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="/static/js/lib/3rdparty/jquery/jquery-1.11.2.min.js"><\/script>')</script>
    <script src="/static/js/lib/3rdparty/modernizr.custom.js"></script>-->



    <?php if ($this->page->type->app_classname == "OrderApplication"): ?>
        <meta name="robots" content="noindex"/>
    <?php endif; ?>
</head>