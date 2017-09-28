<div class="uk-form-row">
    <label class="uk-form-label">
        <?= $this->title ?>
    </label>
    <div class="uk-form-controls">

        <div class="js-cms_json_input__container">
            <textarea style="display: none;" class="js-cms_json_input" name="<?= $this->name ?>"></textarea>


            <? if ($this->has("json_schema")): ?>
                <?
                $schema = $this->json_schema;
                ?>

                <table class="uk-table uk-table-condensed">
                    <thead>
                        <tr>
                            <? foreach ($schema as $key => $title): ?>
                                <th>
                                    <?= $title ?>
                                </th>
                            <? endforeach; ?>
                            <th style="width: 150px;"></th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr class="js-cms_json_input__row-template" style="display: none;">
                            <? foreach ($schema as $key => $title): ?>
                                <td data-key_name="<?= $key ?>">
                                </td>
                            <? endforeach; ?>

                            <th class="uk-text-right">
                                <div class="uk-button-group js-cms_json_input__actions_preview">
                                    <a class="js-cms_json_input__edit_btn uk-button uk-button-small" title="Редактировать">
                                        <i class="uk-icon-pencil"></i>
                                    </a>
                                    <a class="js-cms_json_input__remove_btn uk-button uk-button-small" title="Удалить">
                                        <i class="uk-icon-trash-o"></i>
                                    </a>
                                </div>
                                <div class="uk-button-group js-cms_json_input__actions_edit" style='display: none;'>
                                    <a class="js-cms_json_input__save_btn uk-button uk-button-small" title="Сохранить изменения">
                                        <i class="uk-icon-save"></i>
                                    </a>
                                    <a class="js-cms_json_input__cancel_btn uk-button uk-button-small" title="Отменить редактирование">
                                        <i class="uk-icon-reply"></i>
                                    </a>
                                </div>
                            </th>
                        </tr>


                        <tr class="js-cms_json_input__add_row">
                            <? foreach ($schema as $key => $title): ?>
                                <td data-key_name="<?= $key ?>">
                                    <input class="uk-width-1-1 uk-form-small" data-key_name="<?= $key ?>" />
                                </td>
                            <? endforeach; ?>

                            <td class="uk-text-right">
                                <button class="uk-button uk-button-small js-cms_json_input__add_btn">
                                    <i class="uk-icon-plus"></i>
                                    добавить
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

            <? endif; ?>

        </div>


        <span class="uk-text-primary uk-text-small">
            <?= $this->info ?>
        </span>
    </div>
</div>