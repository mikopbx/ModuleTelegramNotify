<form class="ui large grey segment form" id="module-telegram-notify-form">
    <div class="seven wide field">
        <label>{{ t._('mod_tgm_Token') }}</label>
        {{ form.render('telegram_api_token') }}
    </div>
    {{ partial("partials/submitbutton",['indexurl':'pbx-extension-modules/index/']) }}
</form>