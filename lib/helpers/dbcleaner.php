<?php

namespace ITG\EmailAuth\Helpers;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use ITG\EmailAuth\ORM\AuthCodesTable;
use ITG\EmailAuth\ORM\UserStoredAuthTable;
use ITG\EmailAuth\AuthManager;

class DBCleaner
{
    public static function removeOldAuthCodes()
    {
        $dbRes = AuthCodesTable::query()
            ->setSelect(['ID', 'DATE_CREATE'])
            ->exec();

        while($authCode = $dbRes->Fetch()){
            $expiriedTime = Option::get(GetModuleID(__FILE__),'itg_email_auth_time_expiried_code', AuthManager::DEFAULT_EXPIRED_TIME);

            $obDateCur = new DateTime();
            $obDateCreate = new DateTime($authCode['DATE_CREATE']);
            
            if(($obDateCur->getTimestamp() - $obDateCreate->getTimestamp()) > $expiriedTime){
                AuthCodesTable::delete($authCode['ID']);
            }
        }

        return 'ITG\EmailAuth\Helpers\DBCleaner::removeOldAuthCodes();';
    }

    public static function removeOldStoredAuth()
    {
        $dbRes = UserStoredAuthTable::query()
            ->setSelect(['ID', 'DATE_CREATE'])
            ->exec();

        while($storedUser = $dbRes->Fetch()){
            $expiriedTime = Option::get(GetModuleID(__FILE__),'itg_email_auth_time_expiried_user', AuthManager::DEFAULT_EXPIRED_USER);

            $obDateCur = new DateTime();
            $obDateCreate = new DateTime($storedUser['DATE_CREATE']);

            if(($obDateCur->getTimestamp() - $obDateCreate->getTimestamp()) > $expiriedTime){
                UserStoredAuthTable::delete($storedUser['ID']);
            }
        }

        return 'ITG\EmailAuth\Helpers\DBCleaner::removeOldStoredAuth();';
    }
}
