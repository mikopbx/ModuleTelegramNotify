<?php
/*
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 9 2020
 */

namespace Modules\ModuleTelegramNotify\Lib;

use MikoPBX\Common\Models\Extensions;
use MikoPBX\Core\Asterisk\AsteriskManager;
use MikoPBX\Core\System\BeanstalkClient;
use MikoPBX\Core\System\Util;
use MikoPBX\Core\Workers\WorkerBase;
use MikoPBX\Core\Workers\WorkerCdr;
use Modules\ModuleTelegramNotify\Models\ModuleTelegramNotifyUsers;
use Exception;

require_once 'Globals.php';

class WorkerTelegramNotifyAMI extends WorkerBase
{

    protected AsteriskManager $am;

    private TelegramNotify $tm;
    private array $numbers = [];
    private int $counter = 0;

    /**
     * Старт работы листнера.
     *
     * @param $argv
     */
    public function start($argv):void
    {
        $this->am = Util::getAstManager();
        $this->tm = new TelegramNotify();
        if (!$this->tm->initialized){
            return;
        }
        $this->setFilter();
        $this->updateNumbers();
        $this->am->addEventHandler("userevent", [$this, "callback"]);

        while (true) {
            $result = $this->am->waitUserEvent(true);
            if ($result === []) {
                // Нужен реконнект.
                usleep(100000);
                $this->am = Util::getAstManager();
                $this->setFilter();
            }
        }
    }

    /**
     * Установка фильтра
     *
     * @return array
     */
    private function setFilter(): array
    {
        $pingTube = $this->makePingTubeName(self::class);
        $params = ['Operation' => 'Add', 'Filter' => 'UserEvent: '.$pingTube];
        $this->am->sendRequestTimeout('Filter', $params);

        $params = ['Operation' => 'Add', 'Filter' => 'UserEvent: CdrConnector'];
        return $this->am->sendRequestTimeout('Filter', $params);
    }

    /**
     * Обновление списка номеров для отслеживания.
     */
    private function updateNumbers(): void
    {
        $this->numbers = [];
        $t_user        = [];
        $settings      = ModuleTelegramNotifyUsers::find("[notify_enable]='1'");
        foreach ($settings as $user) {
            $t_user[$user->pbx_user_id] = $user;
        }

        $extensions = Extensions::find('userid IS NOT NULL');
        foreach ($extensions as $extension) {
            if ( ! isset($t_user[$extension->userid])) {
                continue;
            }
            $p_key                 = $this->getPhoneIndex($extension->number);
            $this->numbers[$p_key] = $t_user[$extension->userid];
        }
    }

    /**
     * Возвращает усеценный слева номер телефона.
     *
     * @param $number
     *
     * @return bool|string
     */
    private function getPhoneIndex($number)
    {
        return substr($number, -10);
    }

    /**
     * Функция обработки оповещений.
     *
     * @param $parameters
     */
    public function callback($parameters): void
    {
        if ($this->replyOnPingRequest($parameters)) {
            $this->counter++;
            if ($this->counter > 5) {
                // Обновляем список номеров. Получаем актуальные настройки.
                // Пинг приходит раз в минуту. Интервал обновления списка номеров 5 минут.
                $this->updateNumbers();
                $this->counter = 0;
            }
            return;
        }

        if ( ! isset($parameters['AgiData'])) {
            return;
        }
        $data = json_decode(base64_decode($parameters['AgiData']), true);
        if ($data['action'] === 'transfer_dial') {
            $this->actionDial($data);
        } elseif ($data['action'] === 'dial') {
            $this->actionDial($data);
        }
    }

    /**
     * @param $data
     */
    private function actionDial($data): void
    {
        $dst = $this->getPhoneIndex($data['dst_num']);
        if (isset($this->numbers[$dst])) {
            $general_src_num = null;
            if ($data['transfer'] === '1') {
                $filter                        = [
                    '(linkedid = {linkedid})',
                    'bind'  => [
                        'linkedid' => $data["linkedid"],
                    ],
                    'order' => 'id',
                    'limit' => 1,
                ];
                $filter['miko_result_in_file'] = true;
                $filter['miko_tmp_db']         = true;

                $client  = new BeanstalkClient(WorkerCdr::SELECT_CDR_TUBE);
                $message = $client->request(json_encode($filter), 2);
                if ($message !== false) {
                    $filename = json_decode($message);
                    if (file_exists($filename)) {
                        $history = json_decode(file_get_contents($filename));
                        if (count($history) > 0) {
                            $general_src_num = $history[0]->src_num;
                        }
                        unlink($filename);
                    }
                } else {
                    Util::sysLogMsg('TelegramNotifyAMI', "Error get data from queue 'WorkerCdr::SELECT_CDR_TUBE'. ");
                }
            }
            $buttons = [];

            if ($general_src_num) {
                $text      = "Переадресация <$general_src_num> от <{$data['src_num']}> на <{$data['dst_num']}";
                $buttons[] = [
                    [
                        "text"          => "Перезвонить на <{$general_src_num}>",
                        "callback_data" => json_encode(['action' => 'makeCall', 'number' => $general_src_num]),
                    ],
                ];
            } else {
                $text = "Входящий от <{$data['src_num']}> на <{$data['dst_num']}>";
            }

            $buttons[] = [
                [
                    "text"          => "Перезвонить на <{$data['src_num']}>",
                    "callback_data" => json_encode(['action' => 'makeCall', 'number' => $data['src_num']]),
                ],
            ];

            $buttons[] = [
                [
                    "text"          => "К основному меню...",
                    "callback_data" => json_encode(['action' => 'startMenu']),
                ],
            ];

            // Входящий телефонный звонок от $data['src_num'] на номер $data['dst_num']
            $reply_markup = [
                "inline_keyboard"   => $buttons,
                "one_time_keyboard" => false, // Can be FALSE (hide keyboard after click)
                "resize_keyboard"   => true   // Can be FALSE (vertical resize)
            ];
            $reply_markup = json_encode($reply_markup);
            /** @var ModuleTelegramNotifyUsers $user_data */
            $user_data = $this->numbers[$dst];
            if ( ! empty($user_data->message_id)) {
                $this->tm->editMessageText(1 * $user_data->chat_id, 1 * $user_data->message_id, $text, $reply_markup);
            }
        }
    }

}

// Start worker process
$workerClassname = WorkerTelegramNotifyAMI::class;
if (isset($argv) && count($argv) > 1) {
    cli_set_process_title($workerClassname);
    try {
        $worker = new $workerClassname();
        $worker->start($argv);
    } catch (Exception $e) {
        global $errorLogger;
        $errorLogger->captureException($e);
        Util::sysLogMsg("{$workerClassname}_EXCEPTION", $e->getMessage());
    }
}
