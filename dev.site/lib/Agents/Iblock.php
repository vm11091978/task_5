<?php

namespace Only\Site\Agents;


class Iblock
{
    public static function clearOldLogs()
    {
        // Здесь напиши свой агент
        \Bitrix\Main\Loader::includeModule('iblock');

        $rsLogs = \CIBlockElement::GetList(['TIMESTAMP_X' => 'DESC'], ['IBLOCK_CODE' => 'LOG'], false, false, ['ID', 'IBLOCK_ID']);
        $arElementsId = Array();
        while ($elementsId = $rsLogs->Fetch()['ID']) {
            $arElementsId[] = $elementsId;
        }

        // вырежем первые 10 элементов и таким образом мы оставим в массиве $arElementsId элементы, которые нужно удалить
        array_splice($arElementsId, 0, 10); 

        foreach ($arElementsId as $elementId) {
            \CIBlockElement::Delete($elementId);
        }

        // $str = implode(', ', $arElementsId);
        // mail('ya.vol-vol@yandex.ru', 'Агент', "$str");

        return "\Only\Site\Agents\Iblock::clearOldLogs();";
    }

    public static function example()
    {
        global $DB;
        if (\Bitrix\Main\Loader::includeModule('iblock')) {
            $iblockId = \Only\Site\Helpers\IBlock::getIblockID('QUARRIES_SEARCH', 'SYSTEM');
            $format = $DB->DateFormatToPHP(\CLang::GetDateFormat('SHORT'));
            $rsLogs = \CIBlockElement::GetList(['TIMESTAMP_X' => 'ASC'], [
                'IBLOCK_ID' => $iblockId,
                '<TIMESTAMP_X' => date($format, strtotime('-1 months')),
            ], false, false, ['ID', 'IBLOCK_ID']);
            while ($arLog = $rsLogs->Fetch()) {
                \CIBlockElement::Delete($arLog['ID']);
            }
        }
        return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
    }
}
