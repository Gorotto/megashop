<div class="uk-form-row js-catalog_item_ids_widget">
    <label class="uk-form-label">
        <input name="<?= $this->name ?>" type="hidden" class="catalog_item_ids_widget__input"/>
        <?= $this->title ?>
    </label>


    <div class="uk-form-controls">
        <div class="catalog_item_ids_widget__item js-catalog_item_ids_widget__item-template" style="display: none;">
            <div class="uk-panel uk-panel-box builder-files_list__item">
                <span class="js-catalog_item_ids_widget__item_title"></span>

                <a class="js-catalog_item_ids_widget__item_remove uk-button uk-button-small uk-float-right" title="Удалить">
                    <i class="uk-icon-trash-o"></i>
                </a>
            </div>
        </div>


        <div class="catalog_item_ids_widget__add_conntrolls uk-margin-top" >
            <div>
                <select name="similar_category" class="js-catalog_item_ids_widget__add_conntrolls-category uk-form-small" style="min-width: 200px">
                    <option value="">…</option>
                </select>

                <select name="similar_entry" class="js-catalog_item_ids_widget__add_conntrolls-entry uk-form-small" style="min-width: 200px">
                    <option value="">…</option>
                </select>

                <button class="uk-button uk-button-primary uk-button-small js-catalog_item_ids_widget__add_conntrolls-button">добавить</button>
            </div>
        </div>

        <div class="catalog_item_ids_widget__add_item_link">
            <a class="js-catalog_item_ids_widget__add btn btn-primary">
                Добавить товар
            </a>
        </div>


        <span class="uk-text-primary uk-text-small">
            <?= $this->info ?>
        </span>
    </div>


    <? if ($this->has("not_use_own_id")): ?>
        <div class="js-catalog_item_ids_widget-not_use_own_id"></div>
    <? endif; ?>
</div>