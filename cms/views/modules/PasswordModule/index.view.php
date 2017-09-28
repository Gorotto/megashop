<div class="uk-width-1-2">
    <form class="uk-form uk-form-horizontal">
        <fieldset>
            <legend>Смена пароля</legend>
            <div class="uk-form-row">
                <label class="uk-form-label">Старый пароль</label>
                <div class="uk-form-controls">
                    <input type="password" id="old-password" class="uk-width-1-1"/>
                </div>
            </div>
            <div class="uk-form-row">
                <label class="uk-form-label">Новый пароль</label>
                <div class="uk-form-controls">
                    <input type="password" id="new-password" class="uk-width-1-1" />
                </div>
            </div>
            <div class="uk-form-row">
                <label class="uk-form-label">Новый пароль, еще раз</label>
                <div class="uk-form-controls">
                    <input type="password" id="new-password-retype" class="uk-width-1-1" />
                </div>
            </div>

            <div class="uk-form-row uk-text-right">
                <button id="change-password" disabled="disabled" class="uk-button uk-button-primary">изменить пароль</button>
            </div>
        </fieldset>
    </form>
</div>

<script type="text/javascript">

    $(document).ready(function()
    {
        $('.password-block input').keypress(function(event) {
            if (event.keyCode == 13 && !$('#change-password').attr('disabled')) {
                $(this).trigger('change');
                $('#change-password').trigger('click');
            }
        });

        // Показ кнопки сохранения
        $('#old-password').bind('keyup change focus blur', function(event) {
            $('#change-password').attr('disabled', !/./.test($(this).val()));
        });

        // Смена пароля
        $('#change-password').click(function(e) {
            // Новый пароль достаточно длинный?
            if ($('#new-password').val().length < 4) {
                alert($('#new-password').val().length ? 'Слишком короткий новый пароль, нужно ввести не меньше четырех символов.' : 'Введите новый пароль.');
                $('#new-password').focus();
                $('#new-password-retype').val('');
                return false;
            }

            // Новый пароль совпадает с его повтором?
            if ($('#new-password').val() != $('#new-password-retype').val()) {
                alert($('#new-password-retype').val().length ? 'Вы неправильно набрали повтор нового пароля. Введите еще раз, пароль и его повтор должны совпадать.' : 'Введите повтор нового пароля.');
                $('#new-password-retype').val('').focus();
                return false;
            }

            // Все проверено, можно отправить запрос
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: '<?= $this->ajaxUri ?>',
                data: {
                    old_password: $('#old-password').val(),
                    new_password: $('#new-password').val()
                },
                success: function(response) {
                    if (response.success) {
                        $('#old-password, #new-password, #new-password-retype').val('').trigger('change').blur();
                        alert('Пароль изменен.');
                    } else {
                        alert(response.message);
                        $('#old-password').focus();
                    }
                },
                error: function() {
                }
            });

            e.preventDefault();
        });

        $('#old-password').focus();
    });

</script>