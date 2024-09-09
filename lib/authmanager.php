<?php

namespace ITG\EmailAuth;

use Bitrix\Main\Security\Password;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Cookie;
use Bitrix\Main\Security\Random;
use ITG\EmailAuth\ORM\AuthCodesTable;
use ITG\EmailAuth\ORM\UserStoredAuthTable;

Loc::loadLanguageFile(__FILE__);

class AuthManager
{
    public const DEFAULT_CODE_LENGTH = 15;
    public const DEFAULT_EXPIRED_TIME = 300;
    public const DEFAULT_EXPIRED_USER = 31536000;
    public const AUTH_COOKIE_NAME = 'ITG_EMAIL_AUTH_USER_STORED';

    public static function initEmailAuth($arParams)
    {   
        if($user = \CUser::GetByLogin($arParams['LOGIN'])->Fetch()){
            if(Password::equals($user['PASSWORD'], $arParams['PASSWORD'], ($arParams['PASSWORD_ORIGINAL'] == 'Y'))){
                if(self::checkStoredAuth($user) || self::checkAuthCode($user)){
                    return true;
                }

                return self::generateAuthCode($user);
            }
        }
    }

    protected static function sendCodeToUser($user, $code)
    {
        $arLIDSites = [];
        $arFileds = [];

        $dbSiteRes = \CSite::GetList($by="", $order="", ['LANGUAGE_ID' => SITE_ID]);

        while($site = $dbSiteRes->Fetch()){
            $arLIDSites = $site['LID'];
        }

        $email = Option::get(GetModuleID(__FILE__), 'itg_email_auth_email', Option::get('main', 'email_from'));

        if(strlen($email) == 0){
            $email = Option::get('main', 'email_from');

            if(strlen($email) == 0){
                return false;
            }
        }

        $arFileds = [
            'LOGIN' => $user['LOGIN'], 
            'EMAIL_TO' => $email,
            'CODE' => $code
        ];

        return \CEvent::SendImmediate(
            EventTypeManager::SEND_CODE_TO_EMAIL_EVENT_TYPE, 
            $arLIDSites,
            $arFileds) == 'Y';  
    }

    protected static function generateAuthCode($user)
    {
        $dbRes = AuthCodesTable::query()
            ->setSelect(['ID', 'DATE_CREATE'])
            ->setFilter([
                'USER_ID' => $user['ID'],
                'USER_SESSION_ID' => bitrix_sessid(),
            ])
            ->setOrder([
                'DATE_CREATE' => 'DESC'
            ])
            ->exec();

        if(isset($_REQUEST['RESET_CODE'])){
            while($authCode = $dbRes->Fetch()){
                AuthCodesTable::delete($authCode['ID']);
            }
        }else if($authCode = $dbRes->Fetch()) {
            $expiriedTime = Option::get(GetModuleID(__FILE__),'itg_email_auth_time_expiried_code', self::DEFAULT_EXPIRED_TIME);

            if(intval($expiriedTime) == 0){
                $expiriedTime = self::DEFAULT_EXPIRED_TIME;
            }

            $obDateCur = new DateTime();
            $obDateCreate = new DateTime($authCode['DATE_CREATE']);
            
            if(($obDateCur->getTimestamp() - $obDateCreate->getTimestamp()) <= $expiriedTime){
                self::returnAuthCodeField();
            }
        }

        $code =  Random::getString(Option::get(GetModuleID(__FILE__), 'itg_email_auth_length_code' ,self::DEFAULT_CODE_LENGTH));
    
        if(self::sendCodeToUser($user, $code)){
            self::addAuthCodeToDB($user, $code);
            self::returnAuthCodeField();
        }

        return false;
    }

    protected static function returnAuthCodeField()
    {
        global $APPLICATION; 
                    
        $APPLICATION->RestartBuffer();

        ob_start();?>
            
            <script type="text/javascript" bxrunfirst="true">
                (function(){
                    var container = document.createElement('div');
                    var html = '<div class="login-popup-field-title"><?= Loc::GetMessage('ITG_EMAIL_AUTH_FIELD_NAME')?></div>' + 
                                '<div class="login-input-wrap">' + 
                                    '<input type="text" class="login-input" onfocus="BX.addClass(this.parentNode, \'login-input-active\')" onblur="BX.removeClass(this.parentNode, \'login-input-active\')" name="AUTH_CODE" value="" tabindex="1">' +
                                    '<div class="login-inp-border"></div>' +
                                '</div>' + 
                                '<div class="login-popup-field-title"></div>' + 
                                '<div class="login-input-wrap" style="padding: 0px; width: 100%">' + 
                                    '<input type="submit" class="login-input" style="background-color: #88aa00;border-radius: 5px; color:#333" onmouseover="this.style.opacity=\'.75\';" onmouseout="this.style.opacity=\'1\';" name="RESET_CODE" value="<?= Loc::getMessage('ITG_EMAIL_AUTH_FIELD_RESET_CODE_TITLE')?>" tabindex="1">' +
                                    '<div class="login-inp-border"></div>' +
                                '</div>';
                
                    container.setAttribute('class', 'login-popup-field');
                    container.setAttribute('id', 'authorize_code');
    
                    container.innerHTML = html;
                    
                    if(!top.BX.adminLogin.form.querySelector('#authorize_code')){
                        top.BX.adminLogin.form.querySelector('#authorize_password').after(container);
                    }
                    
                    setTimeout(top.BX.delegate(function(){
                        top.BX.removeClass(this.container, 'login-loading-active');

                        var error = {
                            'TITLE': '<?= Loc::GetMessage('ITG_EMAIL_AUTH_FIELD_NOTICE_TITLE') ?>',
                            'MESSAGE':'<?= Loc::GetMessage('ITG_EMAIL_AUTH_FIELD_NOTICE_MESSAGE') ?>',
                            'TYPE':'ERROR',
                            'CAPTCHA':false
                        }
    
                        top.BX.adminLogin.showError('AUTH_CODE', error);
    
                        this.showCaptcha(error);
                    }, top.BX.adminLogin.current_form), 400);
                }())
            </script>
        <?
        $script = ob_get_contents();
        ob_end_clean();

        echo $script;

        die();
    } 

    protected static function returnAuthCodeError($message)
    {
        global $APPLICATION; 
                    
        $APPLICATION->RestartBuffer();

        ob_start();?>
            
            <script type="text/javascript" bxrunfirst="true">
                (function(){    
                    top.BX.addClass(top.BX.adminLogin.current_form.container, 'login-popup-error-shake');
                    
                    setTimeout(top.BX.delegate(function(){
                        top.BX.removeClass(this.container, 'login-loading-active');
                        top.BX.removeClass(this.container, 'login-popup-error-shake');
                        
                        var error = {
                            'TITLE': '<?= Loc::GetMessage('ITG_EMAIL_AUTH_FIELD_NOTICE_CODE_TITLE') ?>',
                            'MESSAGE':'<?= $message ?>',
                            'TYPE':'ERROR',
                            'CAPTCHA':false
                        }
    
                        top.BX.adminLogin.showError('AUTH_CODE', error);
    
                        this.showCaptcha(error);
                    }, top.BX.adminLogin.current_form), 400);
                }())
            </script>
        <?
        $script = ob_get_contents();
        ob_end_clean();

        echo $script;

        die();
    } 

    protected static function checkAuthCode($user)
    {
        if(isset($_REQUEST['AUTH_CODE']) && !isset($_REQUEST['RESET_CODE'])){
            $code = trim($_REQUEST['AUTH_CODE']);

            if(strlen($code) == 0){
                return self::returnAuthCodeError(Loc::GetMessage('ITG_EMAIL_AUTH_FIELD_NOTICE_INVALID_CODE_MESSAGE'));
            }

            $dbRes = AuthCodesTable::query()
                ->setSelect(['ID', 'DATE_CREATE'])
                ->setFilter([
                    'USER_ID' => $user['ID'],
                    'USER_SESSION_ID' => bitrix_sessid(),
                    'USER_AUTH_CODE' => $code
                ])
                ->setOrder([
                    'DATE_CREATE' => 'DESC'
                ])
                ->exec();

            if($authCode = $dbRes->Fetch()){
                AuthCodesTable::delete($authCode['ID']);

                $expiriedTime = Option::get(GetModuleID(__FILE__),'itg_email_auth_time_expiried_code', self::DEFAULT_EXPIRED_TIME);

                if(intval($expiriedTime) == 0){
                    $expiriedTime = self::DEFAULT_EXPIRED_TIME;
                }

                $obDateCur = new DateTime();
                $obDateCreate = new DateTime($authCode['DATE_CREATE']);
                
                if(($obDateCur->getTimestamp() - $obDateCreate->getTimestamp()) > $expiriedTime){
                    return self::returnAuthCodeError(Loc::GetMessage('ITG_EMAIL_AUTH_FIELD_NOTICE_EXPIRE_CODE_MESSAGE'));
                }

                self::saveUserAuth($user);
                
                return true;
            }

            return self::returnAuthCodeError(Loc::GetMessage('ITG_EMAIL_AUTH_FIELD_NOTICE_INVALID_CODE_MESSAGE'));
        }

        return false;
    }

    protected static function addAuthCodeToDB($user, $code)
    {
        $res = AuthCodesTable::add([
            'USER_ID' => $user['ID'],
            'USER_SESSION_ID' => bitrix_sessid(),
            'USER_AUTH_CODE' => $code
        ]);

        return $res->isSuccess();
    }

    protected static function checkStoredAuth($user)
    {
        $authCookie = Application::getInstance()->getContext()->getRequest()->getCookie(self::AUTH_COOKIE_NAME);

        if(strlen($authCookie)){
            $dbRes = UserStoredAuthTable::query()
                ->setSelect(['ID', 'DATE_CREATE'])
                ->setFilter([
                    'USER_ID' => $user['ID'],
                    'USER_HASH' => $authCookie
                ])
                ->setOrder([
                    'DATE_CREATE' => 'DESC'
                ])
                ->exec();

            if($storedUser = $dbRes->Fetch()){
                $expiriedTime = Option::get(GetModuleID(__FILE__),'itg_email_auth_time_expiried_user', self::DEFAULT_EXPIRED_USER);

                if(intval($expiriedTime) == 0){
                    $expiriedTime = self::DEFAULT_EXPIRED_USER;
                }

                $obDateCur = new DateTime();
                $obDateCreate = new DateTime($storedUser['DATE_CREATE']);

                if(($obDateCur->getTimestamp() - $obDateCreate->getTimestamp()) > $expiriedTime){
                    UserStoredAuthTable::delete($storedUser['ID']);

                    return false;
                }
                
                return true;
            }
        }

        return false;
    }

    protected static function saveUserAuth($user)
    {
        $hash = md5(Random::getString(32));

        $res = UserStoredAuthTable::add([
            'USER_ID' => $user['ID'],
            'USER_HASH' => $hash 
        ]);

        if($res->isSuccess()){
            $cookie = new Cookie(self::AUTH_COOKIE_NAME, $hash, time() + Option::get(GetModuleID(__FILE__),'itg_email_auth_time_expiried_user', self::DEFAULT_EXPIRED_USER));
            $cookie->setSecure(true);
            
            Application::getInstance()->getContext()->getResponse()->addCookie($cookie);

            return true;
        }

        return false;
    }
}
