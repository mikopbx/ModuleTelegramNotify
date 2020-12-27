<?php
/**
 * Copyright (C) MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Nikolay Beketov, 6 2020
 *
 */

namespace Modules\ModuleTelegramNotify\Lib;

use MikoPBX\Core\System\BeanstalkClient;
use MikoPBX\Core\System\Util;
use Exception;
use MikoPBX\Core\Workers\WorkerBase;

require_once 'Globals.php';

class WorkerTelegramMenu extends WorkerBase
{
    /**
     * Старт работы листнера.
     *
     * @param $argv
     */
    public function start($argv): void
    {
        /**
         * this method check for get message(command) and then execute the
         * command function
         *
         * @param bool $sleep
         */
        $tg = new TelegramNotify();
        if (!$tg->initialized){
            return;
        }
        $result = $tg->getUpdates();

        $client = new BeanstalkClient(self::class);
        $client->subscribe($this->makePingTubeName(self::class), [$this, 'pingCallBack']);

        while (true) {
            $update_id = $result['update_id'] ?? 1;
            $result    = $tg->getUpdates($update_id + 1);
            if ( ! $result) {
                $client->wait(1);
                continue;
            }
            if (!empty($tg->all_callback)) {
                call_user_func($tg->all_callback, $this, $result);
            } elseif (isset($result['callback_query'])) {
                $tg->callbackQuery($result['callback_query']);
            } elseif (isset($result['message'])) {
                $tg->callbackMessage($result['message']);
            }
        }
    }
}

// Start worker process
$workerClassname = WorkerTelegramMenu::class;
if (isset($argv) && count($argv) > 1) {
    cli_set_process_title($workerClassname);
    try {
        $worker = new $workerClassname();
        $worker->start($argv);
    } catch (\Throwable $e) {
        global $errorLogger;
        $errorLogger->captureException($e);
        Util::sysLogMsg("{$workerClassname}_EXCEPTION", $e->getMessage(), LOG_ERR);
    }
}
