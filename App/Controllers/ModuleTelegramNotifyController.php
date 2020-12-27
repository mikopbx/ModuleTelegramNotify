<?php
/**
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 12 2018
 */
namespace Modules\ModuleTelegramNotify\App\Controllers;
use MikoPBX\Modules\PbxExtensionUtils;
use Modules\ModuleTelegramNotify\App\Forms\ModuleTelegramNotifyForm;
use Modules\ModuleTelegramNotify\Models\ModuleTelegramNotify;
use MikoPBX\AdminCabinet\Controllers\BaseController;

class ModuleTelegramNotifyController extends BaseController
{
    private $moduleUniqueID = 'ModuleTelegramNotify';
    private $moduleDir;

    /**
     * Basic initial class
     */
    public function initialize(): void
    {
        $this->moduleDir           = PbxExtensionUtils::getModuleDir($this->moduleUniqueID);
        $this->view->logoImagePath = "{$this->url->get()}assets/img/cache/{$this->moduleUniqueID}/logo.svg";
        $this->view->submitMode    = null;
        parent::initialize();
    }

    /**
     * Форма настроек модуля
     */
    public function indexAction(): void
    {
        $footerCollection = $this->assets->collection('footerJS');
        $footerCollection->addJs('js/pbx/main/form.js', true);
        $footerCollection->addJs("js/cache/{$this->moduleUniqueID}/module-telegram-index.js", true);

        $settings = ModuleTelegramNotify::findFirst();
        if ($settings === null) {
            $settings = new ModuleTelegramNotify();
        }

        $this->view->form = new ModuleTelegramNotifyForm($settings);
        $this->view->pick("{$this->moduleDir}/App/Views/index");
    }

    /**
     * Сохранение настроек
     */
    public function saveAction(): void
    {
        if ( ! $this->request->isPost()) {
            return;
        }
        $data   = $this->request->getPost();
        $record = ModuleTelegramNotify::findFirst();

        if ($record === null) {
            $record = new ModuleTelegramNotify();
        }
        $this->db->begin();
        foreach ($record as $key => $value) {
            switch ($key) {
                case 'id':
                    break;
                default:
                    if ( ! array_key_exists($key, $data)) {
                        $record->$key = '';
                    } else {
                        $record->$key = $data[$key];
                    }
            }
        }

        if ($record->save() === false) {
            $errors = $record->getMessages();
            $this->flash->error(implode('<br>', $errors));
            $this->view->success = false;
            $this->db->rollback();

            return;
        }

        $this->flash->success($this->translation->_('ms_SuccessfulSaved'));
        $this->view->success = true;
        $this->db->commit();
    }

}