<?php
/**
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 12 2019
 */


namespace Modules\ModuleTelegramNotify\Lib;

use CURLFile;
use MikoPBX\Common\Models\Extensions;
use MikoPBX\Core\System\BeanstalkClient;
use MikoPBX\Core\System\Processes;
use MikoPBX\Core\System\Util;
use MikoPBX\Core\Workers\Cron\WorkerSafeScriptsCore;
use MikoPBX\Core\Workers\WorkerCdr;
use MikoPBX\Modules\PbxExtensionBase;
use MikoPBX\Modules\PbxExtensionUtils;
use Modules\ModuleTelegramNotify\Models\ModuleTelegramNotify;
use Modules\ModuleTelegramNotify\Models\ModuleTelegramNotifyUsers;

class TelegramNotify extends PbxExtensionBase
{
    public const ACTION_TYPING = 'typing';

    /**
     * returned json from telegram api parse to object and save to result
     *
     * @var
     */
    public $result;
    public bool $initialized = false;
    /**
     * all_callbacks all for events
     *
     * @var array
     */
    public  array  $all_callback = [];
    private string $api = 'https://api.telegram.org/bot';
    /**
     * available telegram bot commands
     *
     * @var array
     */
    private array $available_commands = [
        'getMe',
        'sendMessage',
        'forwardMessage',
        'sendPhoto',
        'sendAudio',
        'sendDocument',
        'sendSticker',
        'sendVideo',
        'sendLocation',
        'sendChatAction',
        'getUserProfilePhotos',
        'getUpdates',
        'setWebhook',
        'editMessageText',
        'pinChatMessage',
    ];

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        /** @var ModuleTelegramNotify $settings */
        $settings = ModuleTelegramNotify::findFirst();
        $token    = ($settings !== null) ? $settings->telegram_api_token : '';
        if (empty($token)) {
            return;
        }
        $this->api         .= $token;
        $this->initialized = true;
    }

    /**
     * Get me
     */
    public function getMe()
    {
        return $this->exec('getMe');
    }

    /**
     * execute telegram api commands
     *
     * @param       $command
     * @param array $params
     *
     * @return mixed|null
     */
    private function exec($command, $params = [])
    {
        $result = [];
        if (in_array($command, $this->available_commands, true)) {
            $contents = $this->curl_get_contents($this->api . '/' . $command, $params);
            $output   = json_decode($contents, true);
            $result   = $this->convertToObject($output);
        }

        return $result;
    }

    /**
     * get the $url content with CURL
     *
     * @param $url
     * @param $params
     *
     * @return mixed
     */
    private function curl_get_contents($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, count($params));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * @param $jsonObject
     *
     * @return mixed|null
     */
    private function convertToObject($jsonObject)
    {
        $result = null;
        if ($jsonObject['ok']) {
            // remove unwanted array elements
            $output = end($jsonObject);
            if (isset($output['message_id'])) {
                $result = $output;
            } else {
                $result = is_array($output) ? end($output) : $output;
            }
        }

        return $result;
    }

    /**
     * add new command to the bot
     *
     * @param array $func
     */
    public function setCallback( array $func):void
    {
        $this->all_callback = $func;
    }

    /**
     * @param null $offset
     * @param int  $limit
     * @param int  $timeout
     *
     * @return mixed|null
     */
    public function getUpdates($offset = null, $limit = 1, $timeout = 1)
    {
        $command = [
            'offset'  => $offset,
            'limit'   => $limit,
            'timeout' => $timeout,
        ];
        return $this->exec('getUpdates', $command);;
    }

    /**
     * Обработка inline запроса.
     *
     * @param $result
     */
    public function callbackQuery($result): void
    {
        if ( ! isset($result['data']) || ! Util::isJson($result['data'])) {
            return;
        }
        $params = json_decode($result['data'], true);
        if ('getHistory' === $params['action']) {
            // Получаем актуальную историю звонков.
            $this->getHistory($result['message']);
        } elseif ('getHistoryNext' === $params['action']) {
            // Следующая страница истории звонков.
            $this->getHistory($result['message'], $params);
        } elseif ('historyDesc' === $params['action']) {
            $this->getHistoryDesc($result['message'], $params);
        } elseif ('getHistoryBack' === $params['action']) {
            // Предыдущая страница истории.
            $this->getHistory($result['message'], $params);
        } elseif ('onNotify' === $params['action']) {
            /** @var ModuleTelegramNotifyUsers $user_data */
            $user_data = ModuleTelegramNotifyUsers::findFirst("chat_id='{$result['message']['chat']['id']}'");
            if ($user_data === null) {
                return;
            }
            $user_data->notify_enable = 1;
            $user_data->save();
            $this->startMenu($result['message']['chat']['id'], $result['message']['message_id']);
        } elseif ('offNotify' === $params['action']) {
            /** @var ModuleTelegramNotifyUsers $user_data */
            $user_data = ModuleTelegramNotifyUsers::findFirst("chat_id='{$result['message']['chat']['id']}'");
            if ($user_data === null) {
                return;
            }
            $user_data->notify_enable = 0;
            $user_data->save();
            $this->startMenu($result['message']['chat']['id'], $result['message']['message_id']);
        } elseif ('makeCall' === $params['action']) {
            $this->makeCall($result['message'], $params);
        } elseif ('getAudio' === $params['action']) {
            $this->getAudio($result['message'], $params);
        } elseif ('startMenu' === $params['action']) {
            $this->startMenu($result['message']['chat']['id'], $result['message']['message_id']);
        }
    }

    /**
     * @param $message
     * @param $params
     */
    private function getHistory($message, $params = null): void
    {
        $user_numbers = $this->getUserExtensions($message['message_id']);
        if (count($user_numbers) === 0) {
            return;
        }

        $additional_q = '';
        $action = $params['action']??'';
        if ('getHistoryBack' === $action) {
            $additional_q = "id<{$params['id']} AND ";
        } elseif ('getHistoryNext' === $action) {
            $additional_q = "id>{$params['id']} AND ";
        }

        $filter = [
            $additional_q . '(src_num IN ({numbers:array}) OR dst_num IN ({numbers:array}) )',
            'bind'    => [
                'numbers' => $user_numbers,
            ],
            'group'   => 'linkedid',
            'columns' => 'linkedid',
            'limit'   => 5,
            'order'   => 'id DESC',
        ];

        $query                         = [
            'columns' => 'id,start,src_num,dst_num,billsec,linkedid',
            'linkedid IN ({linkedid:array})',
            'bind'    => [
                'linkedid' => null,
            ],
            'order'   => 'id',
        ];
        $filter['add_pack_query']      = $query;
        $filter['miko_result_in_file'] = true;

        $client   = new BeanstalkClient(WorkerCdr::SELECT_CDR_TUBE);
        $cdr_data = $client->request(json_encode($filter), 2);
        if ($cdr_data == false) {
            Util::sysLogMsg('TelegramNotify', "Error get data from queue 'WorkerCdr::SELECT_CDR_TUBE'. ", LOG_ERR);
            return;
        }
        $filename = json_decode($cdr_data);
        if ( ! file_exists($filename)) {
            return;
        }
        $history = json_decode(file_get_contents($filename));
        unlink($filename);

        $res_history = [];
        $row_id_min  = null;
        $row_id_max  = null;
        foreach ($history as $row) {
            if ( ! isset($res_history[$row->linkedid])) {
                $res_history[$row->linkedid] = [
                    'id'       => $row->id,
                    'linkedid' => $row->linkedid,
                    'number'   => (in_array($row->src_num, $user_numbers)) ? $row->dst_num : $row->src_num,
                    'start'    => $row->start,
                    'billsec'  => 1 * $row->billsec,
                    'incoming' => (in_array($row->src_num, $user_numbers)) ? false : true,
                ];
            } else {
                $res_history[$row->linkedid]['billsec'] += $row->billsec;
            }

            if ( ! $row_id_min) {
                $row_id_min = $row->id;
            } else {
                $row_id_min = min($row_id_min, $row->id);
            }

            if ( ! $row_id_max) {
                $row_id_max = $row->id;
            } else {
                $row_id_max = max($row_id_max, $row->id);
            }
        }

        if (count($res_history) === 0 && isset($params['id'])) {
            $row_id_max = $params['id'];
            $row_id_min = $params['id'];
        }

        $buttons   = [];
        $buttons[] = [
            [
                "text"          => "<<-- ",
                "callback_data" => json_encode(['action' => 'getHistoryBack', 'id' => $row_id_min]),
            ],
        ];
        foreach ($res_history as $row) {
            $callback_data = [
                'action' => 'historyDesc',
                'm_id'   => 1 * $row_id_min - 1,
                'id'     => $row['id'],
            ];
            if ($row['incoming']) {
                $direction = 'вх. с';
            } else {
                $direction = 'исх. на';
            }
            $clock = "\xF0\x9F\x95\x90";

            $buttons[] = [
                [
                    "text"          => date(
                            'd-m-Y H:i:s',
                            strtotime($row['start'])
                        ) . " {$direction} {$row['number']}, {$clock} {$row['billsec']}c.",
                    "callback_data" => json_encode($callback_data),
                ],
            ];
        }

        $buttons[] = [
            [
                "text"          => "-->>",
                "callback_data" => json_encode(['action' => 'getHistoryNext', 'id' => $row_id_max]),
            ],
            [
                "text"          => "обратно в меню...",
                "callback_data" => json_encode(['action' => 'startMenu']),
            ],
        ];

        $reply_markup = [
            "inline_keyboard"   => $buttons,
            "one_time_keyboard" => false, // Can be FALSE (hide keyboard after click)
            "resize_keyboard"   => true   // Can be FALSE (vertical resize)
        ];
        $reply_markup = json_encode($reply_markup);
        $this->editMessageText($message['chat']['id'], $message['message_id'], "История звонков:", $reply_markup);
    }

    /**
     * @param string $message_id
     * @param bool   $assoc_array
     *
     * @return array
     */
    private function getUserExtensions($message_id, $assoc_array = false): array
    {
        $user_numbers = [];
        $user_data    = ModuleTelegramNotifyUsers::findFirst("message_id='{$message_id}'");
        if ($user_data === null) {
            return $user_numbers;
        }

        $ext_data     = Extensions::find("userid={$user_data->pbx_user_id}");
        $user_numbers = [];
        foreach ($ext_data as $extension) {
            if ($assoc_array) {
                if ('EXTERNAL' === $extension->type) {
                    $id_key = 'peer_mobile';
                } elseif ('SIP' === $extension->type) {
                    $id_key = 'peer_number';
                } else {
                    continue;
                }
                $user_numbers[$id_key] = $extension->number;
            } else {
                if (strlen($extension->number) > 10) {
                    $user_numbers[] = '8' . substr($extension->number, -10);
                    $user_numbers[] = '7' . substr($extension->number, -10);
                } else {
                    $user_numbers[] = $extension->number;
                }
            }
        }

        return $user_numbers;
    }

    /**
     * @param $chat_id
     * @param $message_id
     * @param $text
     * @param $reply_markup
     *
     * @return mixed|null
     */
    public function editMessageText($chat_id, $message_id, $text, $reply_markup)
    {
        $data = [
            'chat_id'      => $this->getChatId($chat_id),
            'message_id'   => $message_id,
            'text'         => $text,
            'reply_markup' => $reply_markup,
        ];

        return $this->exec('editMessageText', $data);
    }

    /**
     * Get current chat id
     *
     * @param null $chat_id
     *
     * @return string |null
     */
    public function getChatId($chat_id = null): ?string
    {
        if ($chat_id) {
            return $chat_id;
        }

        $res = $this->result->message->chat->id;

        return $res;
    }

    /**
     * Отображает расшифровку телефонного звонка.
     *
     * @param $message
     * @param $params
     */
    private function getHistoryDesc($message, $params): void
    {
        $user_numbers = $this->getUserExtensions($message['message_id']);
        if (count($user_numbers) === 0) {
            return;
        }

        $filter = [
            'id={id}',
            'bind'    => [
                'id' => $params['id'],
            ],
            'columns' => 'id,start,src_num,dst_num,billsec,linkedid',
            'limit'   => 1,
        ];

        $filter['miko_result_in_file'] = true;
        $client                        = new BeanstalkClient(WorkerCdr::SELECT_CDR_TUBE);
        $cdr_data                      = $client->request(json_encode($filter), 2);
        if ($cdr_data == false) {
            Util::sysLogMsg('TelegramNotify', "Error get data from queue 'WorkerCdr::SELECT_CDR_TUBE'. ", LOG_ERR);

            return;
        }
        $filename = json_decode($cdr_data);
        if ( ! file_exists($filename)) {
            return;
        }
        $history = json_decode(file_get_contents($filename));
        unlink($filename);
        $buttons = [];

        $start_date = '';
        foreach ($history as $row) {
            $start_date = date('d-m-Y H:i:s', strtotime($row->start));
            $buttons[]  = [
                [
                    "text"          => '<<-- ',
                    "callback_data" => json_encode(['action' => 'getHistoryNext', 'id' => $params['m_id']]),
                ],
            ];
            if (in_array($row->src_num, $user_numbers)) {
                // исходящий
                $num = $row->dst_num;
            } else {
                // входящий
                $num = $row->src_num;
            }
            $buttons[] = [
                [
                    "text"          => "Перезвонить на <{$num}>",
                    "callback_data" => json_encode(['action' => 'makeCall', 'number' => $num]),
                ],
                [
                    "text"          => 'Получить файл записи',
                    "callback_data" => json_encode(['action' => 'getAudio', 'id' => $params['id']]),
                ],
            ];
        }

        $reply_markup = [
            "inline_keyboard"   => $buttons,
            "one_time_keyboard" => false, // Can be FALSE (hide keyboard after click)
            "resize_keyboard"   => true   // Can be FALSE (vertical resize)
        ];
        $reply_markup = json_encode($reply_markup);
        $this->editMessageText(
            $message['chat']['id'],
            $message['message_id'],
            "Детализация звонка {$start_date}:",
            $reply_markup
        );
    }

    /**
     * Отображает начальное меню.
     *
     * @param      $chat_id
     * @param null $message_id
     *
     * @return array
     */
    private function startMenu($chat_id, $message_id = null):array
    {
        $user_data = ModuleTelegramNotifyUsers::findFirst("chat_id='{$chat_id}'");
        if ($user_data === null) {
            return [];
        }
        $notify_enable = $user_data->notify_enable;

        $text         = "Выберите действие:";
        $reply_markup = [
            "inline_keyboard"   => [
                [
                    [
                        "text"          => "Мои звонки",
                        "callback_data" => json_encode(['action' => 'getHistory']),
                    ],
                ],
                [
                    [
                        "text"          => ($notify_enable) ? "Отключить уведомления" : 'Включить уведомления',
                        "callback_data" => json_encode(['action' => ($notify_enable) ? 'offNotify' : 'onNotify']),
                    ],
                ],
            ],
            "one_time_keyboard" => false, // Can be FALSE (hide keyboard after click)
            "resize_keyboard"   => true   // Can be FALSE (vertical resize)
        ];
        $reply_markup = json_encode($reply_markup);

        if ($message_id) {
            $result = $this->editMessageText($chat_id, $message_id, $text, $reply_markup);
        } else {
            $result = $this->sendMessage($text, $chat_id, null, false, null, $reply_markup);
        }

        return $result??[];
    }

    /**
     * send message
     *
     * @param        $text
     * @param        $chat_id
     * @param string $parse_mode
     * @param bool   $disable_web_page_preview
     * @param null   $reply_to_message_id
     * @param null   $reply_markup
     *
     * @return mixed|null
     */
    public function sendMessage(
        $text,
        $chat_id,
        $parse_mode = 'Markdown',
        $disable_web_page_preview = false,
        $reply_to_message_id = null,
        $reply_markup = null
    ) {
        $this->sendChatAction(self::ACTION_TYPING, $chat_id);
        $data = [
            'chat_id'                  => $this->getChatId($chat_id),
            'text'                     => $text,
            'parse_mode'               => $parse_mode,
            'disable_web_page_preview' => $disable_web_page_preview,
            'reply_to_message_id'      => $reply_to_message_id,
            'reply_markup'             => $reply_markup,
        ];
        $res  = $this->exec('sendMessage', $data);

        return $res;
    }

    /**
     * send chat action : Telegram::ACTION_TYPING , ...
     *
     * @param      $action
     * @param null $chat_id
     *
     * @return mixed|null
     */
    public function sendChatAction($action, $chat_id = null)
    {
        $data = [
            'chat_id' => $this->getChatId($chat_id),
            'action'  => $action,
        ];
        $res  = $this->exec('sendChatAction', $data);

        return $res;
    }

    /**
     * Выполняем телефонный звонок от имени пользователя.
     *
     * @param $message
     * @param $params
     */
    private function makeCall($message, $params)
    {
        $user_numbers = $this->getUserExtensions($message['message_id'], true);
        if (count($user_numbers) == 0) {
            return;
        }
        $peer_number = '';
        $peer_mobile = '';
        if (isset($user_numbers['peer_number'])) {
            $peer_number = $user_numbers['peer_number'];
        }
        if (isset($user_numbers['peer_mobile'])) {
            $peer_mobile = $user_numbers['peer_mobile'];
        }
        Util::amiOriginate($peer_number, $peer_mobile, $params['number']);
    }

    /**
     * @param $message
     * @param $params
     *
     */
    private function getAudio($message, $params):void
    {
        $user_numbers = $this->getUserExtensions($message['message_id'], true);
        if (count($user_numbers) === 0) {
            return;
        }

        $filter = [
            'id={id}',
            'bind'    => [
                'id' => $params['id'],
            ],
            'columns' => 'id,start,src_num,dst_num,recordingfile',
            'limit'   => 1,
        ];

        $filter['miko_result_in_file'] = true;
        $client                        = new BeanstalkClient(WorkerCdr::SELECT_CDR_TUBE);
        $cdr_data                      = $client->request(json_encode($filter), 2);
        if ($cdr_data == false) {
            Util::sysLogMsg('TelegramNotify', "Error get data from queue 'WorkerCdr::SELECT_CDR_TUBE'. ", LOG_ERR);
            return;
        }
        $filename = json_decode($cdr_data);
        if ( ! file_exists($filename)) {
            return;
        }
        $history = json_decode(file_get_contents($filename));
        unlink($filename);
        foreach ($history as $row) {
            if ( ! file_exists($row->recordingfile)) {
                Util::sysLogMsg('TelegramNotify', 'Recording file not found. ', LOG_WARNING);
                continue;
            }
            $this->sendAudio(
                $message['chat']['id'],
                $row->recordingfile,
                " {$row->start} с {$row->src_num} на {$row->dst_num}"
            );
        }
    }

    /**
     * @param        $chat_id
     * @param        $filePath
     * @param string $message
     */
    public function sendAudio($chat_id, $filePath, $message = '')
    {
        $cfile = new CURLFile(realpath($filePath));
        $data  = [
            'chat_id' => $chat_id,
            'audio'   => $cfile,
            'caption' => $message,
        ];

        $ch = curl_init($this->api . '/sendAudio');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * @param array $result
     */
    public function callbackMessage(array $result): void
    {
        $chat_id = $result['chat']['id']??'';
        if (trim($result['text']) === '/start') {
            $text         = 'Для авторизации необходимо предоставить Ваш номер телефона. Для этого нажмите на кнопку "Login".';
            $reply_markup = [
                "keyboard"          => [
                    [
                        [
                            "text"            => "Login",
                            "request_contact" => true,
                        ],
                    ],
                ],
                "one_time_keyboard" => true, // Can be FALSE (hide keyboard after click)
                "resize_keyboard"   => true   // Can be FALSE (vertical resize)
            ];
            $reply_markup = json_encode($reply_markup);
            $this->sendMessage($text, $chat_id, null, false, null, $reply_markup);
        } elseif (trim($result['text']) === '/menu') {
            $this->startMenu($chat_id);
        } elseif (isset($result['contact']) && $result['contact']['user_id'] === $result['from']['id']) {
            $phone = $result['contact']['phone_number'];
            $p_key = substr($phone, -10);
            $exten = Extensions::findFirst("number LIKE '%{$p_key}'");
            if ($exten === null || empty($exten->userid)) {
                $text = "У Вас еще нет права получать уведомления. Обратитесь к администратору. $phone";
                $this->sendMessage($text, $chat_id);
                return;
            }
            $user_settings = ModuleTelegramNotifyUsers::findFirst("pbx_user_id='{$exten->userid}'");
            if ($user_settings === null) {
                $user_settings = new ModuleTelegramNotifyUsers();
            }
            $user_settings->telegram_user_id = $result['contact']['user_id'];
            $user_settings->pbx_user_id      = $exten->userid;
            $user_settings->chat_id          = $chat_id;
            $user_settings->mobile_phone     = $phone;
            $user_settings->notify_enable    = 1;
            $result = $this->startMenu($chat_id);
            $user_settings->message_id = $result['message_id']??'';
            $user_settings->save();
        } else {
            $this->sendMessage("Эта команда пока не поддерживается", $chat_id);
        }
    }

    /**
     * @param      $chat_id
     * @param      $message_id
     * @param bool $disable_notification
     *
     * @return mixed|null
     */
    public function pinChatMessage($chat_id, $message_id, $disable_notification = false)
    {
        $data = [
            'chat_id'              => $this->getChatId($chat_id),
            'message_id'           => $message_id,
            'disable_notification' => ($disable_notification === true ? 'true' : 'false'),
        ];
        $res  = $this->exec('pinChatMessage', $data);

        return $res;
    }

    /**
     * @param      $from_id
     * @param      $message_id
     * @param null $chat_id
     *
     * @return mixed|null
     */
    public function forwardMessage($from_id, $message_id, $chat_id = null)
    {
        $data = [
            'chat_id'      => $this->getChatId($chat_id),
            'from_chat_id' => $from_id,
            'message_id'   => $message_id,
        ];
        $res  = $this->exec('forwardMessage', $data);

        return $res;
    }

    /**
     * Start or restart module workers
     *
     * @param bool $restart
     */
    public function startAllServices(bool $restart = false): void
    {
        $moduleEnabled = PbxExtensionUtils::isEnabled($this->moduleUniqueId);
        if ( ! $moduleEnabled) {
            return;
        }
        $configClass      = new TelegramNotifyConf();
        $workersToRestart = $configClass->getModuleWorkers();

        if ($restart) {
            foreach ($workersToRestart as $moduleWorker) {
                Processes::processPHPWorker($moduleWorker['worker']);
            }
        } else {
            $safeScript = new WorkerSafeScriptsCore();
            foreach ($workersToRestart as $moduleWorker) {
                if ($moduleWorker['type'] === WorkerSafeScriptsCore::CHECK_BY_AMI) {
                    $safeScript->checkWorkerAMI($moduleWorker['worker']);
                } else {
                    $safeScript->checkWorkerBeanstalk($moduleWorker['worker']);
                }
            }
        }
    }

}