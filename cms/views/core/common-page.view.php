<!DOCTYPE html>
<html>

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><? if ($this->has('title')): ?><?= $this->title ?> - <? endif ?><?= Config::get('common.site_title') ?> - OAMI <?= Builder::$version ?></title>

        <!--<link rel="stylesheet" href="/cms/static/3rdparty/uikit/css/uikit.css">-->
        <link rel="stylesheet" href="/cms/static/3rdparty/uikit/css/uikit.almost-flat.css">
        <!--<link rel="stylesheet" href="/cms/static/3rdparty/uikit/css/uikit.gradient.min.css">-->

        <link rel="stylesheet" href="/cms/static/3rdparty/uikit/css/components/sortable.css">
        <link rel="stylesheet" href="/cms/static/3rdparty/uikit/css/components/nestable.css">
        <link rel="stylesheet" href="/cms/static/3rdparty/uikit/css/components/notify.almost-flat.min.css">
        <!--<link rel="stylesheet" href="/cms/static/3rdparty/uikit/css/components/sortable.almost-flat.min.css">-->

        <link rel="stylesheet" href="/cms/static/3rdparty/datetimepicker/jquery.datetimepicker.css">
        <link rel="stylesheet" href="/cms/static/3rdparty/chosen/chosen.min.css">

        <link rel="stylesheet" href="/cms/static/css/uikit_overwrite.css">
        <link rel="stylesheet" href="/cms/static/css/base.css">

        <script src="/cms/static/js/jquery-1.11.1.min.js"></script>

        <script src="/cms/static/3rdparty/uikit/js/uikit.min.js"></script>
        <script src="/cms/static/3rdparty/uikit/js/components/sortable.min.js"></script>
        <script src="/cms/static/3rdparty/uikit/js/components/nestable.js"></script>
        <script src="/cms/static/3rdparty/uikit/js/components/notify.min.js"></script>

        <!--<script src="/cms/static/js/jquery.json.js"></script>-->
        <!--<script src="/cms/static/js/jquery.form.js"></script>-->
        <script src="/cms/static/js/ajaxupload.3.7.js"></script>
        <script src="/cms/static/3rdparty/ckeditor/ckeditor.js"></script>
        <script src="/cms/static/3rdparty/datetimepicker/jquery.datetimepicker.js"></script>
        <script src="/cms/static/3rdparty/chosen/chosen.jquery.min.js"></script>

        <script src="/cms/static/js/improved.js"></script>
        <script src="/cms/static/js/nami.js"></script>
        <script src="/cms/static/js/meta.js"></script>
        <script src="/cms/static/js/builder.js"></script>

        <script>Builder.language = <?= json_encode(NamiCore::getLanguage()->name) ?>;</script>
        <script src="/cms/static/js/builder_initialization.js"></script>
    </head>

    <body>
        <div class="uk-progress uk-progress-striped uk-active" id="ajax-notifier">
            <div class="uk-progress-bar" style="width: 100%;">
                идет обработка запроса
            </div>
        </div>

        <nav class="uk-navbar uk-navbar-attached">
            <div class="uk-container uk-container-center">

                <div class="uk-navbar-brand builder-brand__title uk-visible-small">
                    <a href="/cms/">
                        <strong>
                            CMS
                        </strong>
                    </a>
                </div>

                <div class="uk-navbar-brand builder-brand__title uk-hidden-small">
                    <a href="/cms/">
                        <strong>
                            CMS OAMI
                        </strong>
                    </a>
                </div>

                <ul class="uk-navbar-nav uk-hidden-small">
                    <li>
                        <a target="_blank" class="uk-navbar-nav-subtitle" href="/">
                            перейти на сайт
                            <div>
                                <?= $_SERVER['HTTP_HOST'] ?>
                                <i class="uk-icon-external-link"></i>
                            </div>
                        </a>
                    </li>
                </ul>


                <ul class="uk-navbar-nav uk-navbar-flip">
                    <?
                    $languages = array_map(create_function('$l', 'return $l->name;'), array_values(NamiCore::getAvailableLanguages()));
                    ?>

                    <?php if (count($languages) >= 2): ?>
                        <?php $active = NamiCore::getLanguage()->name; ?>
                        <li class="uk-parent" data-uk-dropdown="{mode:'click'}">
                            <a>
                                <i class="uk-icon-language"></i>
                                мультиязычность
                                <i class="uk-icon-caret-down"></i>
                            </a>

                            <div class="uk-dropdown uk-dropdown-navbar uk-dropdown-flip">
                                <ul class="uk-nav uk-nav-navbar">

                                    <li class="uk-nav-header">Язык заполнения</li>

                                    <?php foreach ($languages as $i): ?>
                                        <?php if ($i == $active): ?>
                                            <li>
                                                <a>
                                                    <i class="uk-icon-angle-right">
                                                    </i>
                                                    <?= ucfirst($i) ?>
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li>
                                                <a href="<?= $this->cmsUri ?>/switchlanguage/<?= $i ?>/">
                                                    <?= ucfirst($i) ?>
                                                </a>
                                            </li>
                                        <?php endif ?>
                                    <?php endforeach ?>

                                </ul>
                            </div>
                        </li>
                    <?php endif; ?>



                    <?php
                    $cur_module = null;
                    $available_modules = Modules()
                        ->filter(array(
                            'id__in' => Session::getInstance()->getUser()->getAvaliableModulesIds()
                        ))
                        ->sortedOrder()
                        ->all();
                    ?>

                    <?php if ($available_modules): ?>
                        <li class="uk-parent" data-uk-dropdown="{mode:'click'}">
                            <a>
                                модули <i class="uk-icon-caret-down"></i>
                            </a>

                            <div class="uk-dropdown uk-dropdown-navbar uk-dropdown-flip">
                                <ul class="uk-nav uk-nav-navbar">
                                    <?php foreach ($available_modules as $module): ?>
                                        <?php
                                        if (Meta::in_uripath($module->name)) {
                                            $cur_module = $module;
                                        }
                                        ?>

                                        <li>
                                            <a href="/cms/<?= $module->name ?>/">
                                                <i class="uk-icon-<?= $module->icon_name ?> uk-icon-mini modules_dropdown__icon"></i>
                                                &nbsp;
                                                <?= $module->title ?>
                                            </a>
                                        </li>
                                    <?php endforeach ?>
                                </ul>
                            </div>
                        </li>
                    <?php endif; ?>


                    <li class="uk-parent" data-uk-dropdown="{mode:'click'}">
                        <a>
                            <i class="uk-icon-user"></i> <?= Session::getInstance()->getUser()->login; ?> <i class="uk-icon-caret-down"></i>
                        </a>

                        <div class="uk-dropdown uk-dropdown-navbar uk-dropdown-flip">
                            <ul class="uk-nav uk-nav-navbar">
                                <li><a href="/cms/password/">сменить пароль</a></li>
                                <li><a href="/cms/settings/">настройки</a></li>
                                <li class="divider"></li>
                                <li>
                                    <a href="/cms/logout/">выход</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>

            </div>
        </nav>

        <?php $is_dashboard = (Meta::getUriPath() == "/cms"); ?>

        <div class="uk-container uk-container-center <?php if ($is_dashboard): ?>uk-margin-large-bottom uk-margin-large-top<?php else: ?>uk-margin-top<?php endif; ?>">

            <?php if (!$is_dashboard): ?>
                <div>

                    <?php if ($cur_module): ?>
                        <i class="uk-icon-<?= $cur_module->icon_name ?> uk-icon-medium"></i>
                        <span class="uk-text-large uk-text-bold uk-margin-left uk-text-bottom">
                            <?php if (Meta::getUriPath() != $this->cmsUri . "/" . $this->moduleUri): ?>
                                <a href="<?= $this->cmsUri ?>/<?= $this->moduleUri ?>/">
                                    <?= $cur_module->title ?>
                                </a>
                            <?php else: ?>
                                <?= $cur_module->title ?>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>

                </div>
                <div class="uk-margin-top">
                    <?= $this->content ?>
                </div>

            <?php else: ?>
                <?= $this->content ?>
            <?php endif; ?>

        </div>



        <?php if ($this->hint): ?>
            <?= new View('core/hint') ?>
        <?php endif ?>


        <footer></footer>

    </body>
</html>
