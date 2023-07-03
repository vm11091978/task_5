<?php

// namespace Only\Site\Handlers;


class Iblock
{
    public function addLog($elementId, $blockId, $code, $name)
    {
        // Здесь напиши свой обработчик
        \Bitrix\Main\Loader::includeModule('iblock');

        $arIBblock = CIBlock::GetList([], ['CODE'=>'LOG'], false, false, ['ID'])->Fetch();
        $IBLOCK_ID = $arIBblock['ID']; // это ID блока, куда мы записываем данные

        if ($blockId != $IBLOCK_ID)
        {
            // Узнаем название инфоблока (в котором мы добавляем/изменяем элемент) по его ID
            $arIBblock2 = CIBlock::GetList([], ['ID'=>$blockId], false, false, ['NAME', 'CODE'])->Fetch();
            $blockName = $arIBblock2['NAME'];
            $blockCode = $arIBblock2['CODE'];
            $nameEndCode = $blockName . ' / ' . $blockCode;

            echo $IBLOCK_ID . ' ' . $blockId . ' ' . $blockName . ' ' . $elementId . ' ' . $code . ' ' . $name . '<br>';

            // Если элемент лежит непосредственно в каком-то разделе, получим Id этого раздела
            $Res = CIBlockElement::GetByID($elementId);
            if ($arItem = $Res->GetNext()) {
                $iblockSectionId = $arItem[IBLOCK_SECTION_ID];
            }

            $chainNameSection = '';
            if ($iblockSectionId)
            {
                $arChainNameSection = static::sectionClose([], $iblockSectionId);
                print_r($arChainNameSection);

                // Получим строку, состоящую из названий разделов (от родителя к ребенку)
                $chainNameSection = implode(' -> ', array_reverse($arChainNameSection)) . ' -> ';
            }

            $obSectionName = CIBlockSection::GetList([], ['IBLOCK_ID' => $IBLOCK_ID]);
            $arSectionName = Array();
            while($sectionName = $obSectionName->Fetch()['NAME']) {
                $arSectionName[] = $sectionName;
            }

            $obSectionId = CIBlockSection::GetList([], ['IBLOCK_ID' => $IBLOCK_ID]);
            $arSectionId = Array();
            while($sectionId = $obSectionId->Fetch()['ID']) {
                $arSectionId[] = $sectionId;
            }

            // Получим массив типа ( [Вакансии / VACANCIES] => 24 [Одежда / clothes] => 32 )
            $combineSectionNameId = array_combine($arSectionName, $arSectionId);

            $ID = $combineSectionNameId[$nameEndCode];
            if (!in_array($nameEndCode, $arSectionName))
            {
                $bs = new CIBlockSection;

                $arFields = Array(
                "IBLOCK_ID" => $IBLOCK_ID,
                "NAME" => $nameEndCode,
                );

                $ID = $bs->Add($arFields);
            }

            $elem = new CIBlockElement;

            $arLoadProductArray = [
                "ACTIVE_FROM" => date('d.m.Y H:i:s'),
                "IBLOCK_SECTION_ID" => $ID,
                "IBLOCK_ID" => $IBLOCK_ID,
                // "PROPERTY_VALUES" => $PROP, // $PROP['CODE'] = $name . ' ' . $code;
                "NAME" => $elementId,
                "PREVIEW_TEXT" => $blockName . ' -> ' . $chainNameSection . $name,
            ];

            $elem->Add($arLoadProductArray);
        }
    }

    static function sectionClose($arSect, $sectionId)
    {
        // Получаем раздел по его ID
        $rSect = CIBlockSection::GetByID($sectionId);
        if($section = $rSect->GetNext()) {

            // Добавляем название раздела в массив
            $arSect[] = $section["NAME"];
            if($section["IBLOCK_SECTION_ID"] > 0) {

                // Повторный запуск функции пока не получим все до корня
                $arSect = static::sectionClose($arSect, $section["IBLOCK_SECTION_ID"]);
            }
        }
        return $arSect;
    }
  
    function OnBeforeIBlockElementAddHandler(&$arFields)
    {
        $iQuality = 95;
        $iWidth = 1000;
        $iHeight = 1000;
        /*
         * Получаем пользовательские свойства
         */
        $dbIblockProps = \Bitrix\Iblock\PropertyTable::getList(array(
            'select' => array('*'),
            'filter' => array('IBLOCK_ID' => $arFields['IBLOCK_ID'])
        ));
        /*
         * Выбираем только свойства типа ФАЙЛ (F)
         */
        $arUserFields = [];
        while ($arIblockProps = $dbIblockProps->Fetch()) {
            if ($arIblockProps['PROPERTY_TYPE'] == 'F') {
                $arUserFields[] = $arIblockProps['ID'];
            }
        }
        /*
         * Перебираем и масштабируем изображения
         */
        foreach ($arUserFields as $iFieldId) {
            foreach ($arFields['PROPERTY_VALUES'][$iFieldId] as &$file) {
                if (!empty($file['VALUE']['tmp_name'])) {
                    $sTempName = $file['VALUE']['tmp_name'] . '_temp';
                    $res = \CAllFile::ResizeImageFile(
                        $file['VALUE']['tmp_name'],
                        $sTempName,
                        array("width" => $iWidth, "height" => $iHeight),
                        BX_RESIZE_IMAGE_PROPORTIONAL_ALT,
                        false,
                        $iQuality);
                    if ($res) {
                        rename($sTempName, $file['VALUE']['tmp_name']);
                    }
                }
            }
        }

        if ($arFields['CODE'] == 'brochures') {
            $RU_IBLOCK_ID = \Only\Site\Helpers\IBlock::getIblockID('DOCUMENTS', 'CONTENT_RU');
            $EN_IBLOCK_ID = \Only\Site\Helpers\IBlock::getIblockID('DOCUMENTS', 'CONTENT_EN');
            if ($arFields['IBLOCK_ID'] == $RU_IBLOCK_ID || $arFields['IBLOCK_ID'] == $EN_IBLOCK_ID) {
                \CModule::IncludeModule('iblock');
                $arFiles = [];
                foreach ($arFields['PROPERTY_VALUES'] as $id => &$arValues) {
                    $arProp = \CIBlockProperty::GetByID($id, $arFields['IBLOCK_ID'])->Fetch();
                    if ($arProp['PROPERTY_TYPE'] == 'F' && $arProp['CODE'] == 'FILE') {
                        $key_index = 0;
                        while (isset($arValues['n' . $key_index])) {
                            $arFiles[] = $arValues['n' . $key_index++];
                        }
                    } elseif ($arProp['PROPERTY_TYPE'] == 'L' && $arProp['CODE'] == 'OTHER_LANG' && $arValues[0]['VALUE']) {
                        $arValues[0]['VALUE'] = null;
                        if (!empty($arFiles)) {
                            $OTHER_IBLOCK_ID = $RU_IBLOCK_ID == $arFields['IBLOCK_ID'] ? $EN_IBLOCK_ID : $RU_IBLOCK_ID;
                            $arOtherElement = \CIBlockElement::GetList([],
                                [
                                    'IBLOCK_ID' => $OTHER_IBLOCK_ID,
                                    'CODE' => $arFields['CODE']
                                ], false, false, ['ID'])
                                ->Fetch();
                            if ($arOtherElement) {
                                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                                \CIBlockElement::SetPropertyValues($arOtherElement['ID'], $OTHER_IBLOCK_ID, $arFiles, 'FILE');
                            }
                        }
                    } elseif ($arProp['PROPERTY_TYPE'] == 'E') {
                        $elementIds = [];
                        foreach ($arValues as &$arValue) {
                            if ($arValue['VALUE']) {
                                $elementIds[] = $arValue['VALUE'];
                                $arValue['VALUE'] = null;
                            }
                        }
                        if (!empty($arFiles && !empty($elementIds))) {
                            $rsElement = \CIBlockElement::GetList([],
                                [
                                    'IBLOCK_ID' => \Only\Site\Helpers\IBlock::getIblockID('PRODUCTS', 'CATALOG_' . $RU_IBLOCK_ID == $arFields['IBLOCK_ID'] ? '_RU' : '_EN'),
                                    'ID' => $elementIds
                                ], false, false, ['ID', 'IBLOCK_ID', 'NAME']);
                            while ($arElement = $rsElement->Fetch()) {
                                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                                \CIBlockElement::SetPropertyValues($arElement['ID'], $arElement['IBLOCK_ID'], $arFiles, 'FILE');
                            }
                        }
                    }
                }
            }
        }
    }

}