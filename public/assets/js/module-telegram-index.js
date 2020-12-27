"use strict";

/*
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 12 2019
 */

/* global globalRootUrl,globalTranslate, Form, Config */
var ModuleTelegramNotify = {
  $formObj: $('#module-telegram-notify-form'),
  $statusToggle: $('#module-status-toggle'),
  validateRules: {
    login: {
      identifier: 'telegram_api_token',
      rules: [{
        type: 'empty',
        prompt: globalTranslate.mod_tgm_ValidateTokenEmpty
      }]
    }
  },
  initialize: function () {
    function initialize() {
      window.addEventListener('ModuleStatusChanged', ModuleTelegramNotify.checkToggle);
      ModuleTelegramNotify.checkToggle();
      ModuleTelegramNotify.initializeForm();
    }

    return initialize;
  }(),

  /**
   * Отслеживание состояния переключателя статуса модуля
   */
  checkToggle: function () {
    function checkToggle() {
      if (ModuleTelegramNotify.$statusToggle.checkbox('is checked')) {// Модуль включен
      } else {// Модуль отключен
        }
    }

    return checkToggle;
  }(),

  /**
   * Применение настроек модуля после изменения данных формы
   */
  applyConfigurationChanges: function () {
    function applyConfigurationChanges() {
      $.api({
        url: "".concat(Config.pbxUrl, "/pbxcore/api/modules/ModuleTelegramNotify/reload"),
        on: 'now',
        successTest: function () {
          function successTest(response) {
            // test whether a JSON response is valid
            return Object.keys(response).length > 0 && response.result === true;
          }

          return successTest;
        }(),
        onSuccess: function () {
          function onSuccess() {}

          return onSuccess;
        }()
      });
    }

    return applyConfigurationChanges;
  }(),
  cbBeforeSendForm: function () {
    function cbBeforeSendForm(settings) {
      var result = settings;
      result.data = ModuleTelegramNotify.$formObj.form('get values');
      return result;
    }

    return cbBeforeSendForm;
  }(),
  cbAfterSendForm: function () {
    function cbAfterSendForm() {
      ModuleTelegramNotify.applyConfigurationChanges();
    }

    return cbAfterSendForm;
  }(),
  initializeForm: function () {
    function initializeForm() {
      Form.$formObj = ModuleTelegramNotify.$formObj;
      Form.url = "".concat(globalRootUrl, "module-telegram-notify/save");
      Form.validateRules = ModuleTelegramNotify.validateRules;
      Form.cbBeforeSendForm = ModuleTelegramNotify.cbBeforeSendForm;
      Form.cbAfterSendForm = ModuleTelegramNotify.cbAfterSendForm;
      Form.initialize();
    }

    return initializeForm;
  }()
};
$(document).ready(function () {
  ModuleTelegramNotify.initialize();
});
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInNyYy9tb2R1bGUtdGVsZWdyYW0taW5kZXguanMiXSwibmFtZXMiOlsiTW9kdWxlVGVsZWdyYW1Ob3RpZnkiLCIkZm9ybU9iaiIsIiQiLCIkc3RhdHVzVG9nZ2xlIiwidmFsaWRhdGVSdWxlcyIsImxvZ2luIiwiaWRlbnRpZmllciIsInJ1bGVzIiwidHlwZSIsInByb21wdCIsImdsb2JhbFRyYW5zbGF0ZSIsIm1vZF90Z21fVmFsaWRhdGVUb2tlbkVtcHR5IiwiaW5pdGlhbGl6ZSIsIndpbmRvdyIsImFkZEV2ZW50TGlzdGVuZXIiLCJjaGVja1RvZ2dsZSIsImluaXRpYWxpemVGb3JtIiwiY2hlY2tib3giLCJhcHBseUNvbmZpZ3VyYXRpb25DaGFuZ2VzIiwiYXBpIiwidXJsIiwiQ29uZmlnIiwicGJ4VXJsIiwib24iLCJzdWNjZXNzVGVzdCIsInJlc3BvbnNlIiwiT2JqZWN0Iiwia2V5cyIsImxlbmd0aCIsInJlc3VsdCIsIm9uU3VjY2VzcyIsImNiQmVmb3JlU2VuZEZvcm0iLCJzZXR0aW5ncyIsImRhdGEiLCJmb3JtIiwiY2JBZnRlclNlbmRGb3JtIiwiRm9ybSIsImdsb2JhbFJvb3RVcmwiLCJkb2N1bWVudCIsInJlYWR5Il0sIm1hcHBpbmdzIjoiOztBQUFBOzs7Ozs7O0FBT0E7QUFDQSxJQUFNQSxvQkFBb0IsR0FBRztBQUM1QkMsRUFBQUEsUUFBUSxFQUFFQyxDQUFDLENBQUMsOEJBQUQsQ0FEaUI7QUFFNUJDLEVBQUFBLGFBQWEsRUFBRUQsQ0FBQyxDQUFDLHVCQUFELENBRlk7QUFHNUJFLEVBQUFBLGFBQWEsRUFBRTtBQUNkQyxJQUFBQSxLQUFLLEVBQUU7QUFDTkMsTUFBQUEsVUFBVSxFQUFFLG9CQUROO0FBRU5DLE1BQUFBLEtBQUssRUFBRSxDQUNOO0FBQ0NDLFFBQUFBLElBQUksRUFBRSxPQURQO0FBRUNDLFFBQUFBLE1BQU0sRUFBRUMsZUFBZSxDQUFDQztBQUZ6QixPQURNO0FBRkQ7QUFETyxHQUhhO0FBYzVCQyxFQUFBQSxVQWQ0QjtBQUFBLDBCQWNmO0FBQ1pDLE1BQUFBLE1BQU0sQ0FBQ0MsZ0JBQVAsQ0FBd0IscUJBQXhCLEVBQStDZCxvQkFBb0IsQ0FBQ2UsV0FBcEU7QUFDQWYsTUFBQUEsb0JBQW9CLENBQUNlLFdBQXJCO0FBQ0FmLE1BQUFBLG9CQUFvQixDQUFDZ0IsY0FBckI7QUFDQTs7QUFsQjJCO0FBQUE7O0FBbUI1Qjs7O0FBR0FELEVBQUFBLFdBdEI0QjtBQUFBLDJCQXNCZDtBQUNiLFVBQUlmLG9CQUFvQixDQUFDRyxhQUFyQixDQUFtQ2MsUUFBbkMsQ0FBNEMsWUFBNUMsQ0FBSixFQUErRCxDQUM5RDtBQUNBLE9BRkQsTUFFTyxDQUNOO0FBQ0E7QUFDRDs7QUE1QjJCO0FBQUE7O0FBNkI1Qjs7O0FBR0FDLEVBQUFBLHlCQWhDNEI7QUFBQSx5Q0FnQ0E7QUFDM0JoQixNQUFBQSxDQUFDLENBQUNpQixHQUFGLENBQU07QUFDTEMsUUFBQUEsR0FBRyxZQUFLQyxNQUFNLENBQUNDLE1BQVoscURBREU7QUFFTEMsUUFBQUEsRUFBRSxFQUFFLEtBRkM7QUFHTEMsUUFBQUEsV0FISztBQUFBLCtCQUdPQyxRQUhQLEVBR2lCO0FBQ3JCO0FBQ0EsbUJBQU9DLE1BQU0sQ0FBQ0MsSUFBUCxDQUFZRixRQUFaLEVBQXNCRyxNQUF0QixHQUErQixDQUEvQixJQUFvQ0gsUUFBUSxDQUFDSSxNQUFULEtBQW9CLElBQS9EO0FBQ0E7O0FBTkk7QUFBQTtBQU9MQyxRQUFBQSxTQVBLO0FBQUEsK0JBT08sQ0FFWDs7QUFUSTtBQUFBO0FBQUEsT0FBTjtBQVdBOztBQTVDMkI7QUFBQTtBQTZDNUJDLEVBQUFBLGdCQTdDNEI7QUFBQSw4QkE2Q1hDLFFBN0NXLEVBNkNEO0FBQzFCLFVBQU1ILE1BQU0sR0FBR0csUUFBZjtBQUNBSCxNQUFBQSxNQUFNLENBQUNJLElBQVAsR0FBY2pDLG9CQUFvQixDQUFDQyxRQUFyQixDQUE4QmlDLElBQTlCLENBQW1DLFlBQW5DLENBQWQ7QUFDQSxhQUFPTCxNQUFQO0FBQ0E7O0FBakQyQjtBQUFBO0FBa0Q1Qk0sRUFBQUEsZUFsRDRCO0FBQUEsK0JBa0RWO0FBQ2pCbkMsTUFBQUEsb0JBQW9CLENBQUNrQix5QkFBckI7QUFDQTs7QUFwRDJCO0FBQUE7QUFxRDVCRixFQUFBQSxjQXJENEI7QUFBQSw4QkFxRFg7QUFDaEJvQixNQUFBQSxJQUFJLENBQUNuQyxRQUFMLEdBQWdCRCxvQkFBb0IsQ0FBQ0MsUUFBckM7QUFDQW1DLE1BQUFBLElBQUksQ0FBQ2hCLEdBQUwsYUFBY2lCLGFBQWQ7QUFDQUQsTUFBQUEsSUFBSSxDQUFDaEMsYUFBTCxHQUFxQkosb0JBQW9CLENBQUNJLGFBQTFDO0FBQ0FnQyxNQUFBQSxJQUFJLENBQUNMLGdCQUFMLEdBQXdCL0Isb0JBQW9CLENBQUMrQixnQkFBN0M7QUFDQUssTUFBQUEsSUFBSSxDQUFDRCxlQUFMLEdBQXVCbkMsb0JBQW9CLENBQUNtQyxlQUE1QztBQUNBQyxNQUFBQSxJQUFJLENBQUN4QixVQUFMO0FBQ0E7O0FBNUQyQjtBQUFBO0FBQUEsQ0FBN0I7QUErREFWLENBQUMsQ0FBQ29DLFFBQUQsQ0FBRCxDQUFZQyxLQUFaLENBQWtCLFlBQU07QUFDdkJ2QyxFQUFBQSxvQkFBb0IsQ0FBQ1ksVUFBckI7QUFDQSxDQUZEIiwic291cmNlc0NvbnRlbnQiOlsiLypcbiAqIENvcHlyaWdodCDCqSBNSUtPIExMQyAtIEFsbCBSaWdodHMgUmVzZXJ2ZWRcbiAqIFVuYXV0aG9yaXplZCBjb3B5aW5nIG9mIHRoaXMgZmlsZSwgdmlhIGFueSBtZWRpdW0gaXMgc3RyaWN0bHkgcHJvaGliaXRlZFxuICogUHJvcHJpZXRhcnkgYW5kIGNvbmZpZGVudGlhbFxuICogV3JpdHRlbiBieSBBbGV4ZXkgUG9ydG5vdiwgMTIgMjAxOVxuICovXG5cbi8qIGdsb2JhbCBnbG9iYWxSb290VXJsLGdsb2JhbFRyYW5zbGF0ZSwgRm9ybSwgQ29uZmlnICovXG5jb25zdCBNb2R1bGVUZWxlZ3JhbU5vdGlmeSA9IHtcblx0JGZvcm1PYmo6ICQoJyNtb2R1bGUtdGVsZWdyYW0tbm90aWZ5LWZvcm0nKSxcblx0JHN0YXR1c1RvZ2dsZTogJCgnI21vZHVsZS1zdGF0dXMtdG9nZ2xlJyksXG5cdHZhbGlkYXRlUnVsZXM6IHtcblx0XHRsb2dpbjoge1xuXHRcdFx0aWRlbnRpZmllcjogJ3RlbGVncmFtX2FwaV90b2tlbicsXG5cdFx0XHRydWxlczogW1xuXHRcdFx0XHR7XG5cdFx0XHRcdFx0dHlwZTogJ2VtcHR5Jyxcblx0XHRcdFx0XHRwcm9tcHQ6IGdsb2JhbFRyYW5zbGF0ZS5tb2RfdGdtX1ZhbGlkYXRlVG9rZW5FbXB0eSxcblx0XHRcdFx0fSxcblx0XHRcdF0sXG5cdFx0fSxcblx0fSxcblx0aW5pdGlhbGl6ZSgpIHtcblx0XHR3aW5kb3cuYWRkRXZlbnRMaXN0ZW5lcignTW9kdWxlU3RhdHVzQ2hhbmdlZCcsIE1vZHVsZVRlbGVncmFtTm90aWZ5LmNoZWNrVG9nZ2xlKTtcblx0XHRNb2R1bGVUZWxlZ3JhbU5vdGlmeS5jaGVja1RvZ2dsZSgpO1xuXHRcdE1vZHVsZVRlbGVncmFtTm90aWZ5LmluaXRpYWxpemVGb3JtKCk7XG5cdH0sXG5cdC8qKlxuXHQgKiDQntGC0YHQu9C10LbQuNCy0LDQvdC40LUg0YHQvtGB0YLQvtGP0L3QuNGPINC/0LXRgNC10LrQu9GO0YfQsNGC0LXQu9GPINGB0YLQsNGC0YPRgdCwINC80L7QtNGD0LvRj1xuXHQgKi9cblx0Y2hlY2tUb2dnbGUoKSB7XG5cdFx0aWYgKE1vZHVsZVRlbGVncmFtTm90aWZ5LiRzdGF0dXNUb2dnbGUuY2hlY2tib3goJ2lzIGNoZWNrZWQnKSkge1xuXHRcdFx0Ly8g0JzQvtC00YPQu9GMINCy0LrQu9GO0YfQtdC9XG5cdFx0fSBlbHNlIHtcblx0XHRcdC8vINCc0L7QtNGD0LvRjCDQvtGC0LrQu9GO0YfQtdC9XG5cdFx0fVxuXHR9LFxuXHQvKipcblx0ICog0J/RgNC40LzQtdC90LXQvdC40LUg0L3QsNGB0YLRgNC+0LXQuiDQvNC+0LTRg9C70Y8g0L/QvtGB0LvQtSDQuNC30LzQtdC90LXQvdC40Y8g0LTQsNC90L3Ri9GFINGE0L7RgNC80Ytcblx0ICovXG5cdGFwcGx5Q29uZmlndXJhdGlvbkNoYW5nZXMoKSB7XG5cdFx0JC5hcGkoe1xuXHRcdFx0dXJsOiBgJHtDb25maWcucGJ4VXJsfS9wYnhjb3JlL2FwaS9tb2R1bGVzL01vZHVsZVRlbGVncmFtTm90aWZ5L3JlbG9hZGAsXG5cdFx0XHRvbjogJ25vdycsXG5cdFx0XHRzdWNjZXNzVGVzdChyZXNwb25zZSkge1xuXHRcdFx0XHQvLyB0ZXN0IHdoZXRoZXIgYSBKU09OIHJlc3BvbnNlIGlzIHZhbGlkXG5cdFx0XHRcdHJldHVybiBPYmplY3Qua2V5cyhyZXNwb25zZSkubGVuZ3RoID4gMCAmJiByZXNwb25zZS5yZXN1bHQgPT09IHRydWU7XG5cdFx0XHR9LFxuXHRcdFx0b25TdWNjZXNzKCkge1xuXG5cdFx0XHR9LFxuXHRcdH0pO1xuXHR9LFxuXHRjYkJlZm9yZVNlbmRGb3JtKHNldHRpbmdzKSB7XG5cdFx0Y29uc3QgcmVzdWx0ID0gc2V0dGluZ3M7XG5cdFx0cmVzdWx0LmRhdGEgPSBNb2R1bGVUZWxlZ3JhbU5vdGlmeS4kZm9ybU9iai5mb3JtKCdnZXQgdmFsdWVzJyk7XG5cdFx0cmV0dXJuIHJlc3VsdDtcblx0fSxcblx0Y2JBZnRlclNlbmRGb3JtKCkge1xuXHRcdE1vZHVsZVRlbGVncmFtTm90aWZ5LmFwcGx5Q29uZmlndXJhdGlvbkNoYW5nZXMoKTtcblx0fSxcblx0aW5pdGlhbGl6ZUZvcm0oKSB7XG5cdFx0Rm9ybS4kZm9ybU9iaiA9IE1vZHVsZVRlbGVncmFtTm90aWZ5LiRmb3JtT2JqO1xuXHRcdEZvcm0udXJsID0gYCR7Z2xvYmFsUm9vdFVybH1tb2R1bGUtdGVsZWdyYW0tbm90aWZ5L3NhdmVgO1xuXHRcdEZvcm0udmFsaWRhdGVSdWxlcyA9IE1vZHVsZVRlbGVncmFtTm90aWZ5LnZhbGlkYXRlUnVsZXM7XG5cdFx0Rm9ybS5jYkJlZm9yZVNlbmRGb3JtID0gTW9kdWxlVGVsZWdyYW1Ob3RpZnkuY2JCZWZvcmVTZW5kRm9ybTtcblx0XHRGb3JtLmNiQWZ0ZXJTZW5kRm9ybSA9IE1vZHVsZVRlbGVncmFtTm90aWZ5LmNiQWZ0ZXJTZW5kRm9ybTtcblx0XHRGb3JtLmluaXRpYWxpemUoKTtcblx0fSxcbn07XG5cbiQoZG9jdW1lbnQpLnJlYWR5KCgpID0+IHtcblx0TW9kdWxlVGVsZWdyYW1Ob3RpZnkuaW5pdGlhbGl6ZSgpO1xufSk7XG5cbiJdfQ==