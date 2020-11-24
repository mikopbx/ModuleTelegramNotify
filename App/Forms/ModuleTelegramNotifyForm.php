<?php
/**
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 12 2018
 */
namespace Modules\ModuleTelegramNotify\App\Forms;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Form;

class ModuleTelegramNotifyForm extends Form
{

    public function initialize($entity = null, $options = null): void
    {
        $this->add(new Text('telegram_api_token'));
    }
}