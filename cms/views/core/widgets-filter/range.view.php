<?
if (array_key_exists('min', $this->filter['value']) && $this->filter['value']['min']) {
    $min_value = $this->filter['value']['min'];
} elseif (array_key_exists('min', $this->filter['available_values'])) {
    $min_value = $this->filter['available_values']['min'];
} else {
    $min_value = '';
}
if (array_key_exists('max', $this->filter['value']) && ($this->filter['value']['max'] || $this->filter['value']['max'] === 0)) {
    $max_value = $this->filter['value']['max'];
} elseif (array_key_exists('max', $this->filter['available_values'])) {
    $max_value = $this->filter['available_values']['max'];
} else {
    $max_value = '';
}
?>
<div class="uk-form-row">
    <label><?= $this->filter['title'] ?></label>

    <div class="uk-grid">
        <div class="uk-width-1-2">
            <input class="uk-width-1-1" type="text" name="<?= $this->filter['field'] ?>_min" value="<?= $min_value ?>">
        </div>

        <div class="uk-width-1-2">
            <input class="uk-width-1-1" type="text" name="<?= $this->filter['field'] ?>_max" value="<?= $max_value ?>">
        </div>
    </div>
</div>