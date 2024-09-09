<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;

$module_id = GetModuleID(__FILE__);

Loc::loadMessages(__FILE__);

// проверка прав на настройки модуля
if ($APPLICATION->GetGroupRight($module_id) < 'S')
{
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

Loader::includeModule($module_id);

$request = HttpApplication::getInstance()->getContext()->getRequest();

$aTabs = [
	[
        "DIV" => "auth_email_settings",
		"TAB" => GetMessage("ITG_EMAIL_AUTH_BASE_SETTINGS"),
        'OPTIONS' => [
            Loc::getMessage('ITG_EMAIL_AUTH_BASE_SETTINGS'),
            [
                'itg_email_auth_email',
                Loc::getMessage('ITG_EMAIL_AUTH_EMAIL'),
                '',
                ['text', 20]
            ],
            ['note'	=> Loc::getMessage('ITG_EMAIL_AUTH_EMAIL_NOTICE')],
            [
                'itg_email_auth_time_expiried_user',
                Loc::getMessage('ITG_EMAIL_AUTH_EXPIRIED_USER'),
                '31536000',
                ['text', 20],
                '',
                '*'
            ],
            [
                'itg_email_auth_time_expiried_code',
                Loc::getMessage('ITG_EMAIL_AUTH_EXPIRIED_CODE'),
                '300',
                ['text', 20],
                '',
                '*'
            ],
            [
                'itg_email_auth_length_code',
                Loc::getMessage('ITG_EMAIL_AUTH_LENGTH_CODE'),
                '15',
                ['text', 20],
                '',
                '*'
            ]
        ]
    ]
];

$requiredFields = [
    'itg_email_auth_time_expiried_user',
    'itg_email_auth_time_expiried_code',
    'itg_email_auth_length_code'
];

// сохранение настроек
if ($request->isPost() && $request['Update'] && check_bitrix_sessid())
{
    try {        
        foreach ($aTabs as $aTab)
        {
            foreach ($aTab['OPTIONS'] as $arOption)
            {
                if (!is_array($arOption)) continue;
                if ($arOption['note']) continue;

                if(in_array($arOption[0], $requiredFields) && isset($_REQUEST[$arOption[0]]) && strlen($_REQUEST[$arOption[0]]) == 0){
                    throw new Exception(Loc::getMessage('ITG_EMAIL_AUTH_FIELD_EMPTY_ERROR', ['#FIELD#' => $arOption[1]]));
                }
    
                __AdmSettingsSaveOption($module_id, $arOption);
            }
        }
    } catch (\Exception $e) {
        CAdminMessage::ShowMessage($e->getMessage());
    }
}
// вывод формы
$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>

<? $tabControl->Begin(); ?>
<form method="POST" action="<?=$APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($request['mid'])?>&lang=<?=$request['lang']?>" name="<?= $module_id . '_settings'?>">

    <?
    foreach ($aTabs as $aTab)
    {
        if ($aTab['OPTIONS'])
        {
            $tabControl->BeginNextTab();
            __AdmSettingsDrawList($module_id, $aTab['OPTIONS']);
        }
    }
    ?>

    <? $tabControl->Buttons(); ?>
    
    <input type="submit" name="Update" value="<?=Loc::getMessage('ITG_EMAIL_AUTH_BTN_SAVE')?>">
    <input type="reset" name="reset" value="<?=Loc::getMessage('ITG_EMAIL_AUTH_BTN_RESET')?>">

    <?=bitrix_sessid_post()?>
</form>
<? $tabControl->End(); ?>