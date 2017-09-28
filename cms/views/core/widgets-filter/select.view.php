<?
$fk_qs = null;
$is_nested_set = false;
if (array_key_exists('model', $this->filter) && $this->filter['model']) {
    $fk_qs = NamiQuerySet($this->filter['model']);
}
if ($fk_qs instanceof NamiNestedSetQuerySet) {
    $is_nested_set = true;
    $fk_qs = $fk_qs->treeOrder()->filterLevel(2, 50);
}

if ($fk_qs) {
    $values_fields = array('id', 'title');
    if ($is_nested_set) {
        $values_fields[] = 'lvl';
    }
    $items = $fk_qs->values($values_fields);
    $values = Meta::getAssocArray($items, 'id');
} else {
    $values = $this->filter['available_values'];
}
?>

<div class="uk-form-row">
    <label class="uk-form-label">
        <?= $this->filter['title'] ?>
    </label>

    <div class="uk-form-controls">
        <select name="<?= $this->filter['field'] ?>" type="text">
            <option value="">…</option>
            <? foreach ($values as $value): ?>
                <?
                if (is_array($value) && array_key_exists('title', $value)) {
                    $val = $value['id'];
                    $title = $value['title'];
                } else {
                    $val = $value;
                    $title = $value;
                }
                ?>
                <? if ($title): ?>
                    <option value="<?= $val ?>" <? if ($val == $this->filter['value']): ?>selected="selected"<? endif ?>>
                        <? if (is_array($value) && array_key_exists('lvl', $value)): ?>
                            <?= str_repeat('&nbsp;', ($value['lvl'] - 2) * 2) ?>
                        <? endif ?>
                        <?= $title ?>
                    </option>
                <? endif ?>
            <? endforeach ?>
        </select>
    </div>
</div>
