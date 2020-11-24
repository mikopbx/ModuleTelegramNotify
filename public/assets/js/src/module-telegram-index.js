/*
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 12 2019
 */

/* global globalRootUrl,globalTranslate, Form, Config */
const ModuleTelegramNotify = {
	$formObj: $('#module-telegram-notify-form'),
	$statusToggle: $('#module-status-toggle'),
	validateRules: {
		login: {
			identifier: 'telegram_api_token',
			rules: [
				{
					type: 'empty',
					prompt: globalTranslate.mod_tgm_ValidateTokenEmpty,
				},
			],
		},
	},
	initialize() {
		window.addEventListener('ModuleStatusChanged', ModuleTelegramNotify.checkToggle);
		ModuleTelegramNotify.checkToggle();
		ModuleTelegramNotify.initializeForm();
	},
	/**
	 * Отслеживание состояния переключателя статуса модуля
	 */
	checkToggle() {
		if (ModuleTelegramNotify.$statusToggle.checkbox('is checked')) {
			// Модуль включен
		} else {
			// Модуль отключен
		}
	},
	/**
	 * Применение настроек модуля после изменения данных формы
	 */
	applyConfigurationChanges() {
		$.api({
			url: `${Config.pbxUrl}/pbxcore/api/modules/ModuleTelegramNotify/reload`,
			on: 'now',
			successTest(response) {
				// test whether a JSON response is valid
				return Object.keys(response).length > 0 && response.result === true;
			},
			onSuccess() {

			},
		});
	},
	cbBeforeSendForm(settings) {
		const result = settings;
		result.data = ModuleTelegramNotify.$formObj.form('get values');
		return result;
	},
	cbAfterSendForm() {
		ModuleTelegramNotify.applyConfigurationChanges();
	},
	initializeForm() {
		Form.$formObj = ModuleTelegramNotify.$formObj;
		Form.url = `${globalRootUrl}module-telegram-notify/save`;
		Form.validateRules = ModuleTelegramNotify.validateRules;
		Form.cbBeforeSendForm = ModuleTelegramNotify.cbBeforeSendForm;
		Form.cbAfterSendForm = ModuleTelegramNotify.cbAfterSendForm;
		Form.initialize();
	},
};

$(document).ready(() => {
	ModuleTelegramNotify.initialize();
});

