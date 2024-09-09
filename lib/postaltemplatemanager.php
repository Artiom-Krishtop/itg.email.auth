<?php

namespace ITG\EmailAuth;

use Bitrix\Main\Security\Password;
use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);

class PostalTemplateManager
{
    public static function installPostalTemplates()
    {
        $dbETRes = \CEventType::GetList(['TYPE_ID' => EventTypeManager::SEND_CODE_TO_EMAIL_EVENT_TYPE]);

        if($eventType = $dbETRes->Fetch()){
            $arLIDSites = [];

            $dbSiteRes = \CSite::GetList();

            while ($site = $dbSiteRes->Fetch()) {
                $arLIDSites[] = $site['LID'];
            }

            $emess = new \CEventMessage;
            $emess->Add([
                "ACTIVE" => "Y",
                "EVENT_NAME" => $eventType['EVENT_NAME'],
                "LID" => $arLIDSites,
                "EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
                "EMAIL_TO" => "#EMAIL_TO#",
                "SUBJECT" => Loc::GetMessage('ITG_WITH_EMAIL_AUTH_POSTAL_TEMPLATE_SUBLECT'),
                "BODY_TYPE" => "text",
                "MESSAGE" => Loc::GetMessage('ITG_WITH_EMAIL_AUTH_POSTAL_TEMPLATE_MESSAGE'),
            ]);
        }
    }

    public static function unInstallPostalTemplates()
    {
        $dbTemplRes = \CEventMessage::GetList($by = '', $order = '', ['TYPE_ID' => EventTypeManager::SEND_CODE_TO_EMAIL_EVENT_TYPE]);

        while ($template = $dbTemplRes->Fetch()) {
            $emess = new \CEventMessage();
            $emess->Delete($template['ID']);
        }
    }
}
