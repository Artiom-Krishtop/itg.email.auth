<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Config\Option;
use ITG\EmailAuth\ORM\AuthCodesTable;
use ITG\EmailAuth\ORM\UserStoredAuthTable;

Loc::loadMessages(__FILE__);

class itg_email_auth extends CModule
{
	public $MODULE_ID = 'itg.email.auth';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;

    public function __construct()
    {
        $arModuleVersion = [];

        include __DIR__ .'/version.php';
        
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('MODULE_DESC');
    }

    public function DoInstall()
    {
        global $APPLICATION;

        if ($this->isVersionD7())
        {
            ModuleManager::registerModule($this->MODULE_ID);

            if(self::IncludeModule($this->MODULE_ID)){
                ITG\EmailAuth\EventTypeManager::installEventTypes();
                ITG\EmailAuth\PostalTemplateManager::installPostalTemplates();

                $this->InstallDB();
                $this->InstallEvents();
                $this->InstallAgents();
            }
        }
        else
        {
            $APPLICATION->ThrowException(Loc::getMessage('IR_INSTALL_ERROR_VERSION'));
        }

        return true;
    }

    public function DoUninstall()
    {
        if(self::IncludeModule($this->MODULE_ID)){
            ITG\EmailAuth\EventTypeManager::unInstallEventTypes();
            ITG\EmailAuth\PostalTemplateManager::unInstallPostalTemplates();

            $this->UnInstallAgents();
            $this->UnInstallEvents();
            $this->UnInstallDB();
        }
        
        ModuleManager::unRegisterModule($this->MODULE_ID);

        return true;
    }

    /**
     * Проверяем версию ядра
     */
    public function isVersionD7()
    {
        return CheckVersion(ModuleManager::getVersion('main'), '14.00.00');
    }

    /**
     * Устанавливаем таблицы базы данных
     */
    public function InstallDB()
    {
        if (!(Application::getConnection()->isTableExists(AuthCodesTable::getTableName()))) {
            AuthCodesTable::getEntity()->createDbTable();
        }

        if (!(Application::getConnection()->isTableExists(UserStoredAuthTable::getTableName()))) {
            UserStoredAuthTable::getEntity()->createDbTable();
        }
    }

    /**
     * Удаляем установленные таблицы
     */
    public function UnInstallDB()
    {
        Option::delete($this->MODULE_ID);

        if (Application::getConnection()->isTableExists(AuthCodesTable::getTableName())) {
            Application::getConnection()->dropTable(AuthCodesTable::getTableName());
        }

        if (Application::getConnection()->isTableExists(UserStoredAuthTable::getTableName())) {
            Application::getConnection()->dropTable(UserStoredAuthTable::getTableName());
        }
    }

    /**
     * Добавляем события
     */
    public function InstallEvents()
    {
        $EventManager = EventManager::getInstance();

        // перехват стандартной авторизации
        $EventManager->registerEventHandler('main', 'OnBeforeUserLogin', $this->MODULE_ID, 'ITG\EmailAuth\AuthManager', 'initEmailAuth');
    }
    
    /**
     * Убираем добавленные события
     */
    public function UnInstallEvents()
    {
        $EventManager = EventManager::getInstance();

        // перехват стандартной авторизации
        $EventManager->unRegisterEventHandler('main', 'OnBeforeUserLogin', $this->MODULE_ID, 'ITG\EmailAuth\AuthManager', 'initEmailAuth');
    }

    /**
     * Добавляем агенты
     */
    public function InstallAgents()
    {
        CAgent::AddAgent('ITG\EmailAuth\Helpers\DBCleaner::removeOldAuthCodes();', $this->MODULE_ID, 'N', 60);
        CAgent::AddAgent('ITG\EmailAuth\Helpers\DBCleaner::removeOldStoredAuth();', $this->MODULE_ID, 'N', 60);
    }
    
    /**
     * Убираем добавленные агенты
     */
    public function UnInstallAgents()
    {
        CAgent::RemoveModuleAgents($this->MODULE_ID);
    }
}