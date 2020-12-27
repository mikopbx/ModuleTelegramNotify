<?php
/**
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 12 2019
 */


namespace Modules\ModuleTelegramNotify\Lib;

use MikoPBX\Core\Workers\Cron\WorkerSafeScriptsCore;
use MikoPBX\Modules\Config\ConfigClass;
use MikoPBX\PBXCoreREST\Lib\PBXApiResult;

class TelegramNotifyConf extends ConfigClass
{

    /**
     * Returns module workers to start it at WorkerSafeScript
     *
     * @return array
     */
    public function getModuleWorkers(): array
    {
        return [
            [
                'type'   => WorkerSafeScriptsCore::CHECK_BY_BEANSTALK,
                'worker' => WorkerTelegramMenu::class,
            ],
            [
                'type'   => WorkerSafeScriptsCore::CHECK_BY_AMI,
                'worker' => WorkerTelegramNotifyAMI::class,
            ],
        ];
    }

    /**
     *  Process CoreAPI requests under root rights
     *
     * @param array $request
     *
     * @return PBXApiResult
     */
    public function moduleRestAPICallback(array $request): PBXApiResult
    {
        $res = new PBXApiResult();
        $res->processor = __METHOD__;
        $action = strtoupper($request['action']);
        switch ($action){
            case 'CHECK':
                $result = null;
                $module = new TelegramNotify();
                if ($module->initialized) {
                    $result = $module->getMe();
                }
                $res->success = $result !== null;
                $res->data   = is_array($result)?$result:[$result];
                break;
            default:
                $res->success = false;
                $res->messages[]='API action not found in moduleRestAPICallback ModuleTelegramNotify';
        }
        return $res;
    }

    /**
     * Process after enable action in web interface
     *
     * @return void
     */
    public function onAfterModuleEnable(): void
    {
        $module = new TelegramNotify();
        if ($module->initialized) {
            $module->startAllServices(true);
        }
    }
}