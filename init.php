<?
// файл /bitrix/php_interface/init.php
// регистрируем обработчики
AddEventHandler("iblock", "OnAfterIBlockElementUpdate", Array("MyClass", "OnAfterIBlockElementUpdateHandler"));
AddEventHandler("iblock", "OnAfterIBlockElementAdd", Array("MyClass", "OnAfterIBlockElementAddHandler"));

require_once($_SERVER['DOCUMENT_ROOT'] . 'local/modules/dev.site/lib/Handlers/Iblock.php');
use \Only\Site\Handlers;

class MyClass
{
    // создаем обработчик события "OnAfterIBlockElementUpdate"
    public static function OnAfterIBlockElementUpdateHandler(&$arFields)
    {
        if ($arFields["RESULT"])
        {
            AddMessage2Log("Запись с кодом ".$arFields["ID"]." изменена.");

            // подключаем модуль dev.site
            if (CModule::IncludeModule("dev.site"))
            {
                // выполняем метод модуля   
                Iblock::addLog($arFields["ID"], $arFields["IBLOCK_ID"], $arFields["CODE"], $arFields["NAME"]);
            }
        }
        else
            AddMessage2Log("Ошибка изменения записи ".$arFields["ID"]." (".$arFields["RESULT_MESSAGE"].").");
    }

    // создаем обработчик события "OnAfterIBlockElementAdd"
    public static function OnAfterIBlockElementAddHandler(&$arFields)
    {
        if ($arFields["ID"]>0)
        {
             AddMessage2Log("Запись с кодом ".$arFields["ID"]." добавлена.");

            // подключаем модуль dev.site
            if (CModule::IncludeModule("dev.site"))
            {
                // выполняем метод модуля
                Iblock::addLog($arFields["ID"], $arFields["IBLOCK_ID"], $arFields["CODE"], $arFields["NAME"]);
            }
        }
        else
             AddMessage2Log("Ошибка добавления записи (".$arFields["RESULT_MESSAGE"].").");
    }
}
