<li data-id="<?= $this->item->id ?>"
    class="uk-nestable-list-item js_cms-item<?= $this->item->enabled ? "" : " builder-list_item-disabled" ?>"
    namiObject="<?= $this->item->id ?>">

    <div class="uk-nestable-item">
        <div class='builder-items_list__item<?php if (!$this->show_drag_interface): ?> js_cms-drag_drop_disabled<?php endif; ?>'>
            <div class="builder-items_list__item_col_middle">
                <div class="builder-list_item__item_content">

                    <div class="uk-grid">
                        <div class="uk-width-1-1">
                            <span class='js_cms-item_title' namiText="title">
                                <?= $item->title ?>
                            </span>

                            <span class="uk-text-muted uk-margin-left" namiText="name">
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
                        <? if ($show_edit_interface): ?>
                        <button title="Редактировать" class="uk-button js_cms-edit_item"><i class="uk-icon-pencil"></i></button>
                        <button title="Отображать на сайте" class="uk-button js_cms-enabled_item" namiField="enabled"><i
                                class="uk-icon-eye<?= $this->item->enabled ? "" : "-slash" ?>"></i></button>
                        <button title="Удалить" class="uk-button js_cms-delete_item"><i class="uk-icon-trash"></i></button>
                        <? endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>