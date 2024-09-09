<?php

CModule::AddAutoloadClasses(
	"itg.email.auth",
	array(
		"ITG\EmailAuth\AuthManager" => "lib/authmanager.php",
		"ITG\EmailAuth\EventTypeManager" => "lib/eventtypemanager.php",
		"ITG\EmailAuth\PostalTemplateManager" => "lib/postaltemplatemanager.php",

		"ITG\EmailAuth\ORM\AuthCodesTable" => "lib/orm/authcodestable.php",
		"ITG\EmailAuth\ORM\UserStoredAuthTable" => "lib/orm/userstoredauthtable.php",

		"ITG\EmailAuth\Helpers\DBCleaner" => "lib/helpers/dbcleaner.php",
	)
);

?>