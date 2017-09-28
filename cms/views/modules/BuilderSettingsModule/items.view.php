<?
$model_name = "BuilderSetting";
?>


<div class="uk-grid">
    <div class="uk-width-1-1">
        &nbsp;
    </div>
</div>

<div class="uk-grid uk-margin-small-bottom">
    <div class="uk-width-1-1">

        <? if (CmsApplication::is_develop_mode()): ?>
            <button class="uk-button uk-button-success uk-button-medium js_cms-create_item">
                <i class="uk-icon-plus"></i>
                <span class="uk-text-bold">
                    добавить запись
                </span>
            </button>
        <? endif; ?>

    </div>
</div>


<div class="js_cms-create_form_place"></div>
<hr class="uk-margin-top-remove uk-margin-small-top">


<div class="builder-items_list__descr">
    <div class="uk-panel uk-margin-small-bottom">
        <div class='builder-items_list__item'>
            <div class="builder-items_list__item_col_middle">
                <div class="builder-list_item__item_content">

                    <div class="uk-grid">
                        <div class="uk-width-2-5">
                            <span class='uk-text-muted'>
                                Название
                            </span>
                        </div>

                        <div class="uk-width-3-5">
                            <span class='uk-text-muted'>
                                Значение
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



<div class="js_cms-items uk-margin-large-bottom" namiModel="<?= $model_name ?>">
    <? foreach ($this->paginator->objects as $item): ?>

        <div class="js_cms-item" namiObject="<?= $item->id ?>">
            <div class='builder-items_list__item'>

                <div class="builder-items_list__item_col_middle">
                    <div class="builder-list_item__item_content">

                        <div class="uk-grid">
                            <div class="uk-width-2-5">
                                <span class='js_cms-item_title' namiText="title">
                                    <?= $item->title ?>
                                </span>
                            </div>

                            <div class="uk-width-3-5">
                                <small namiText="value"><?= $item->value ?></small>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="builder-items_list__item_col_right">
                    <div class="uk-text-right">
                        <div class="uk-button-group">
                            <button style="display: none" class="uk-button"></button>
                            <button class="uk-button js_cms-edit_item"><i class="uk-icon-pencil"></i></button>

                            <? if (CmsApplication::is_develop_mode()): ?>
                                <button class="uk-button js_cms-delete_item"><i class="uk-icon-trash"></i></button>
                            <? endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    <? endforeach; ?>
</div>


<div class="uk-margin-large-top">
    <?= $this->paginator ?>
</div>


<div class="js_cms-item" id="js_cms-item_preview_template" style="display:none;">
    <div class='builder-items_list__item'>


        <div class="builder-items_list__item_col_middle">
            <div class="builder-list_item__item_content">

                <div class="uk-grid">
                    <div class="uk-width-2-5">
                        <span class='js_cms-item_title' namiText="title"></span>
                    </div>

                    <div class="uk-width-3-5">
                        <small namiText="value"></small>
                    </div>
                </div>

            </div>
        </div>

        <div class="builder-items_list__item_col_right">
            <div class="uk-text-right">
                <div class="uk-button-group">
                    <button style="display: none" class="uk-button"></button>
                    <button class="uk-button js_cms-edit_item"><i class="uk-icon-pencil"></i></button>

                    <? if (CmsApplication::is_develop_mode()): ?>
                        <button class="uk-button js_cms-delete_item"><i class="uk-icon-trash"></i></button>
                    <? endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>


<div class='uk-width-1-1' id="js_cms-item_edit_form" style="display:none;">
    <div class="builder-add_item_form">
        <form class="uk-form uk-form-horizontal">

            <? if (CmsApplication::is_develop_mode()): ?>
                <div class="uk-form-row">
                    <label class="uk-form-label">
                        Название (рус)
                    </label>
                    <div class="uk-form-controls">
                        <input type="text" class="uk-width-1-1" name="title">
                    </div>
                </div>

                <div class="uk-form-row">
                    <label class="uk-form-label">
                        Название (англ)
                    </label>
                    <div class="uk-form-controls">
                        <input type="text" class="uk-width-1-1" name="name">
                    </div>
                </div>

                <div class="uk-form-row">
                    <label class="uk-form-label">
                        Отображать параметр в панели
                    </label>
                    <div class="uk-form-controls">
                        <input type="checkbox" name="visible" />
                    </div>
                </div>

            <? else: ?>

                <div class="uk-form-row">
                    <fieldset data-uk-margin>
                        <legend>
                            <span namiText="title"></span>

                            <div class='uk-float-right'>
                                <span class='uk-text-muted' namiText="name"></span>
                            </div>
                        </legend>
                    </fieldset>
                </div>

            <? endif; ?>


            <div class="uk-form-row">
                <label class="uk-form-label">
                    Значение
                </label>
                <div class="uk-form-controls">
                    <input type="text" class="uk-width-1-1" name="value">
                </div>
            </div>



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



<script>
    $(function() {
        var itemForm = Object.create(Nami.Form);

        Builder
            .Interface
            .modelForm(itemForm)
            .listActions(itemForm);

    });
</script>