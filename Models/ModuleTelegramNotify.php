<?php
/**
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 12 2018
 */

namespace Modules\ModuleTelegramNotify\Models;

use MikoPBX\Modules\Models\ModulesModelsBase;

class ModuleTelegramNotify extends ModulesModelsBase
{

    /**
     * @Primary
     * @Identity
     * @Column(type="integer", nullable=false)
     */
    public $id;

    /**
     * Telegram api token username
     *
     * @Column(type="string", nullable=true)
     */
    public $telegram_api_token;


    public function initialize(): void
    {
        $this->setSource('m_ModuleTelegramNotify');
        parent::initialize();
    }
}