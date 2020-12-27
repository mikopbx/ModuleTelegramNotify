<?php
/**
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 12 2018
 */

namespace Modules\ModuleTelegramNotify\Models;

use MikoPBX\Common\Models\Users;
use MikoPBX\Modules\Models\ModulesModelsBase;
use Phalcon\Mvc\Model\Relation;

class ModuleTelegramNotifyUsers extends ModulesModelsBase
{
    /**
     * @Primary
     * @Identity
     * @Column(type="integer", nullable=false)
     */
    public $id;

    /**
     *
     * @Column(type="integer", nullable=true)
     */
    public $telegram_user_id;

    /**
     *
     * @Column(type="integer", nullable=true)
     */
    public $pbx_user_id;

    /**
     *
     * @Column(type="integer", nullable=true)
     */
    public $chat_id;

    /**
     *
     * @Column(type="integer", nullable=true)
     */
    public $message_id;

    /**
     *
     * @Column(type="integer", nullable=true)
     */
    public $notify_enable;

    /**
     *
     * @Column(type="string", nullable=true)
     */
    public $mobile_phone;

    /**
     * Returns dynamic relations between module models and common models
     * MikoPBX check it in ModelsBase after every call to keep data consistent
     *
     * There is example to describe the relation between Providers and ModuleTemplate models
     *
     * It is important to duplicate the relation alias on message field after Models\ word
     *
     * @param $calledModelObject
     *
     * @return void
     */
    public static function getDynamicRelations(&$calledModelObject): void
    {
        if (is_a($calledModelObject, Users::class)) {
            $calledModelObject->hasOne(
                'id',
                __CLASS__,
                'pbx_user_id',
                [
                    'alias'      => 'ModuleTelegramNotifyUsers',
                    'foreignKey' => [
                        'allowNulls' => 0,
                        'message'    => 'Models\ModuleTelegramNotifyUsers',
                        'action'     => Relation::ACTION_CASCADE,
                    ],
                ]
            );
        }
    }

    public function initialize(): void
    {
        $this->setSource('m_ModuleTelegramNotifyUsers');
        parent::initialize();
        $this->belongsTo(
            'pbx_user_id',
            Users::class,
            'id',
            [
                'alias'      => 'PBXUsers',
                'foreignKey' => [
                    'allowNulls' => false,
                    'action'     => Relation::NO_ACTION,
                ],
            ]
        );
    }
}