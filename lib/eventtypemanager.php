<?php

namespace ITG\EmailAuth;

use Bitrix\Main\Security\Password;
use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);

class EventTypeManager
{
    const SEND_CODE_TO_EMAIL_EVENT_TYPE = 'USER_SEND_CODE_TO_EMAIL';

    public static function installEventTypes()
    {
        $eventType  = new \CEventType();
        $eventType->Add([
            'LID' => 'ru',
            'EVENT_NAME' => self::SEND_CODE_TO_EMAIL_EVENT_TYPE,
            'NAME'=> Loc::GetMessage('ITG_EMAIL_AUTH_EVENT_TYPE_NAME'),
            'DESCRIPTION'=> Loc::GetMessage('ITG_EMAIL_AUTH_EVENT_TYPE_DESCRIPTION')
        ]);
    }

    public static function unInstallEventTypes()
    {
        $eventType  = new \CEventType();
        $eventType->Delete(self::SEND_CODE_TO_EMAIL_EVENT_TYPE);
    }
}
