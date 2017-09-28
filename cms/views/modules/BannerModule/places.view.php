<?
$model_name = "BannerPlace";
?>

<div class="uk-grid">
    <div class="uk-width-1-1">
        <? if (CmsApplication::is_develop_mode()): ?>

            <ul class="uk-subnav uk-subnav-pill">
                <li><a href="<?= $this->uri ?>/">баннеры</a></li>
                <li><a href="<?= $this->uri ?>/places/">баннерные места</a></li>
            </ul>

        <? endif; ?>
    </div>
</div>

<div class="uk-grid uk-margin-small-bottom">
    <div class="uk-width-2-10">

        <button class="uk-button uk-button-success uk-button-medium js_cms-create_item">
            <i class="uk-icon-plus"></i>
            <span class="uk-text-bold">
                добавить место
            </span>
        </button>

    </div>
</div>


<div class="js_cms-create_form_place"></div>

<div class="builder-items_list__descr">
    <div class="uk-panel uk-margin-small-bottom">
        <div class='builder-items_list__item'>
            <div class="builder-items_list__item_col_middle">
                <div class="builder-list_item__item_content">

                    <div class="uk-grid">
                        <div class="uk-width-1-2">
                            <span class='uk-text-muted'>
                                Название
                            </span>
                        </div>

                        <div class="uk-width-1-2">
                            <span class='uk-text-muted'>
                                name
                            </span>
                        </div>
                    </div>

                </div>
            </div>

            <div class="builder-items_list__item_col_right">
                &nbsp;
            </div>
        </div>
    </div>
</div>

<hr class="uk-margin-top-remove uk-margin-small-top">

<div class="js_cms-items uk-margin-large-bottom" namiModel=<?= $model_name ?>>

    <? foreach ($this->paginator->objects as $item): ?>
        <div class="js_cms-item<?= $item->enabled ? "" : " builder-list_item-disabled" ?>" namiObject="<?= $item->id ?>">
            <div class='builder-items_list__item'>

                <div class="builder-items_list__item_col_middle">
                    <div class="builder-list_item__item_content">
                        <div class="uk-grid">
                            <div class="uk-width-1-2">
                                <span class='js_cms-item_title' namiText="title">
                                    <?= $item->title ?>
                                </span>
                            </div>
                            <div class="uk-width-1-2">
                                <span namiText="name">
                                    <?= $item->name ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="builder-items_list__item_col_right">
                    <div class="uk-text-right">
                        <div class="uk-button-group">
                            <button style="display: none" class="uk-button"></button>
                            <button class="uk-button js_cms-copy_item"><i class="uk-icon-copy"></i></button>
                            <button class="uk-button js_cms-edit_item"><i class="uk-icon-pencil"></i></button>
                            <button class="uk-button js_cms-enabled_item" namiField="enabled"><i class="uk-icon-eye<?= $item->enabled ? "" : "-slash" ?>"></i></button>
                            <button class="uk-button js_cms-delete_item"><i class="uk-icon-trash"></i></button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    <? endforeach; ?>

</div>


<div class="uk-grid">
    <div class="uk-width-1-1">
        <?= $this->paginator ?>
    </div>
</div>


<div class='uk-width-1-1' id="js_cms-item_edit_form" style="display:none;">
    <div class="builder-add_item_form">
        <form class="uk-form uk-form-horizontal">
            <?= NamiFormGenerator::forModel($model_name); ?>

            <div class="uk-form-row">
                <div class="uk-text-right uk-margin-large-top">
                    <button type="button" class="uk-button uk-button-success js_cms_item_edit_form__save">
                        <i class="uk-icon-save"></i>
                        Сохранить
                    </button>

                    <button type="button" class="uk-button uk-button-danger js_cms_item_edit_form__cancel">
                        Отмена
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<div class="js_cms-item" id="js_cms-item_preview_template" style="display:none;">
    <div class='builder-items_list__item'>

        <div class="builder-items_list__item_col_middle">
            <div class="builder-list_item__item_content">

                <div class="uk-grid">
                    <div class="uk-width-1-2">
                        <span class='js_cms-item_title' namiText="title">
                        </span>
                    </div>
                    <div class="uk-width-1-2">
                        <span namiText="name">
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="builder-items_list__item_col_right">
            <div class="uk-text-right">
                <div class="uk-button-group">
                    <button style="display: none" class="uk-button"></button>
                    <button class="uk-button js_cms-copy_item"><i class="uk-icon-copy"></i></button>
                    <button class="uk-button js_cms-edit_item"><i class="uk-icon-pencil"></i></button>
                    <button class="uk-button js_cms-enabled_item" namiField="enabled"><i class="uk-icon-eye"></i></button>
                    <button class="uk-button js_cms-delete_item"><i class="uk-icon-trash"></i></button>
                </div>
            </div>
        </div>

    </div>
</div>



<script>
    $(function() {
        var itemForm = Object.create(Nami.Form);

        Builder.Interface
            .modelForm(itemForm)
            .listActions(itemForm);           
            

    });
</script>