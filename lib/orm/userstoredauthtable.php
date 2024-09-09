<?php

namespace ITG\EmailAuth\ORM;

use Bitrix\Main\Entity,
    Bitrix\Main\Type;

class UserStoredAuthTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'itg_email_auth_user_stored_auth';
    }

    public static function getMap()
    {
        return array(
            (new Entity\IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
        
            (new Entity\IntegerField('USER_ID'))
                ->configureRequired(),

            (new Entity\StringField('USER_HASH'))
                ->configureRequired(),

            (new Entity\DateTimeField('DATE_CREATE'))
                ->configureRequired()
                ->configureDefaultValue(new Type\DateTime()),
        );
    }
}