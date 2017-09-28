<?
$model_name = "CatalogFieldSetField";
?>

<div class="uk-grid">
    <div class="uk-width-1-1 uk-margin-top">
        <ul class="uk-subnav uk-subnav-pill">
            <?= new View($this->getFilePath() . "/block_subnavi") ?>
        </ul>

        <h4>
            Набор полей: «<?= $this->fieldset->title ?>»
        </h4>
    </div>
</div>



<div class="uk-grid uk-margin-small-bottom">
    <div class="uk-width-2-10">

        <button class="uk-button uk-button-success uk-button-medium js_cms-create_item">
            <i class="uk-icon-plus"></i>
            <span class="uk-text-bold">
                добавить запись
            </span>
        </button>

    </div>
</div>


<div class="js_cms-create_form_place"></div>
<hr class="uk-margin-top-remove uk-margin-small-top">


<div class="js_cms-items uk-margin-large-bottom" namiModel=<?= $model_name ?>>

    <? foreach ($this->items as $item): ?>
        <div class="js_cms-item" namiObject="<?= $item->id ?>">
            <div class='builder-items_list__item'>

                <div class="builder-items_list__item_col_middle">
                    <div class="builder-list_item__item_content">
                        <div class="uk-grid">
                            <div class="uk-width-1-1">
                                <span class='js_cms-item_title' namiText="title">
                                    <?= $item->title ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="builder-items_list__item_col_right">
                    <div class="uk-text-right">
                        <div class="uk-button-group">
                            <button style="display: none" class="uk-button"></button>
                            <button class="uk-button js_cms-edit_item"><i class="uk-icon-pencil"></i></button>
                            <button class="uk-button js_cms-delete_item"><i class="uk-icon-trash"></i></button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    <? endforeach; ?>

</div>


<div class='uk-width-1-1' id="js_cms-item_edit_form" style="display:none;">
    <div class="builder-add_item_form">
        <form class="uk-form uk-form-horizontal">
            <?= NamiFormGenerator::forModel($model_name); ?>

            <? if ($this->fields_list): ?>
                <div class="uk-form-row">
                    <label class="uk-form-label">
                        Поле
                    </label>
                    <div class="uk-form-controls">

                        <select name="field">
                            <option value="">…</option>
                            <? foreach ($this->fields_list as $field): ?>
                                <option value="<?= $field['id'] ?>">
                                    <?= $field['title'] ?>
                                </option>
                            <? endforeach; ?>
                        </select>

                    </div>
                </div>
            <? endif; ?>


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
                    <div class="uk-width-1-1">
                        <span class='js_cms-item_title' namiText="title">
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="builder-items_list__item_col_right">
            <div class="uk-text-right">
                <div class="uk-button-group">
                    <button style="display: none" class="uk-button"></button>
                    <button class="uk-button js_cms-edit_item"><i class="uk-icon-pencil"></i></button>
                    <button class="uk-button js_cms-delete_item"><i class="uk-icon-trash"></i></button>
                </div>
            </div>
        </div>

    </div>
</div>



<script>
    $(function() {
        var itemForm = Object.create(Nami.Form)
            .extend({
                extraData: {fieldset: <?= $this->fieldset->id ?>}
            });

        Builder.Interface
            .modelForm(itemForm)
            .listActions(itemForm)
            .sortable(itemForm);

    });
</script>