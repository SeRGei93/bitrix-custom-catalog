<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\ArgumentNullException;
use \Bitrix\Main\ArgumentTypeException;
use \Bitrix\Main\LoaderException;
use \Bitrix\Main\SystemException;

/**
 * <?$APPLICATION->IncludeComponent("oip:iblock.section.list","",[
 *   "IBLOCK_ID" => 2,
 *   "BASE_SECTION" => 8,
 *   "DEPTH" => 3,
 *   "SHOW_ELEMENTS_CNT" => false,
 *   "USER_FIELDS" => array("UF_*")
 *   ])?>
 */
class COipIblockSectionList extends \CBitrixComponent
{
    /** @var array $arSections Массив с разделами */
    private $arSections = array();
    /** @var array $arUserFields Массив типов пользовательских полей */
    private $arUserFields;
    /** @var array $arUFListValues Массив значений пользовательских свойств для полей типа "Список" */
    private $arUFListValues;
    /** @var array $arFileFields Массив с идентификаторами файлов */
    private $arFiles = array();
    /** @var array $arFileFields Массив с ссылками на UF_ поля типа "Список" */
    private $arFieldsWithEnumerationValues = array();
    /** @var array $arFileFields Массив с ссылками на UF_ поля типа "Файл" */
    private $arFieldsWithFileValues = array();
    /** @var array $arFileFields Массив с ссылками на UF_ поля типа "Привязка к элементам инф. блока" */
    private $arFieldsWithIBlockElementValues = array();

    public function onPrepareComponentParams($arParams)
    {
        return  $this->initParams($arParams);
    }

    public function executeComponent()
    {
        if(empty($this->arResult["EXCEPTION"])) {
            try {
                if (!\Bitrix\Main\Loader::includeModule("iblock")) {
                    throw new \Bitrix\Main\SystemException("Module iblock is not installed");
                }

                $this->execute();
            } catch (LoaderException $e) {
                $this->arResult["EXCEPTION"] = $e->getMessage();
            }
            catch (SystemException $e) {
                $this->arResult["EXCEPTION"] = $e->getMessage();
            }
        }

        $this->includeComponentTemplate();
    }

    protected function execute() {

        // Получение разделов
        $this->arSections = $this->getSectionList();

        // Если $baseSection пришел пустым - значит выводить нужно относительно самого верхнего уровня
        if (!isset($this->arParams["BASE_SECTION"]) || ($this->arParams["BASE_SECTION"] == 0)) {
            $sectionArray = $this->arSections;
        }
        else {
            $sectionArray = $this->extractSectionFromArray(
                $this->arSections,
                $this->arParams["FILTER_FIELD_NAME"],
                $this->arParams["BASE_SECTION"]
            );
        }

        // Строим дерево, относительно указанного раздела с указанной глубиной вложенности
        $this->buildSectionArray($sectionArray, 0, $this->arParams["DEPTH"]);

        // Получение значений для полей типа "список"
        // 1. Получим все значения, которые могут принимать пользовательские поля с типом "список"
        $this->getUserTypeListValues();
        // 2. Проставляем значения для полей с типом "список"
        $this->updateEnumerationValues();

        // Получение значений для полей типа "файл"
        // 1. Запрашиваем информацию по файлам
        $this->getFileValues();
        // 2. Проставляем значения для полей с типом "file"
        $this->updateFileValues();

        $this->arResult["SECTIONS"] = $sectionArray;
    }

    /**
     * @return array $arParams
     * @throws ArgumentNullException | ArgumentTypeException
     * @return array
     */
    protected function initParams($arParams) {
        try {
            // ID инфоблока, внутри которого просматриваются разделы
            if(!is_set($arParams["IBLOCK_ID"])) {
                throw new \Bitrix\Main\ArgumentNullException("IBLOCK_ID");
            }
            if(!intval($arParams["IBLOCK_ID"])) {
                throw new \Bitrix\Main\ArgumentTypeException("IBLOCK_ID");
            }
        }
        catch (\Bitrix\Main\ArgumentException $e) {
            $this->arResult["EXCEPTION"] = $e->getMessage();
        }

        // ID или код раздела, относительно которого начнется построение дерева
        if(!is_set($arParams["BASE_SECTION"])) {
            $arParams["BASE_SECTION"] = 0;
        }

        // Поля для выборки
        if(!is_set($arParams["SELECT"])) {
            $arParams["SELECT"] = array("*");
        }

        // UF_ поля для выборки
        if(!is_set($arParams["USER_FIELDS"])) {
            $arParams["USER_FIELDS"] = array();
        }

        // Поле, по которому производится выборка раздела
        $arParams["FILTER_FIELD_NAME"] = is_int($arParams["BASE_SECTION"]) ? "ID": "CODE";

        // Флаг - показывать или скрывать количество элементов в категории
        if(!is_set($arParams["SHOW_ELEMENTS_CNT"])) {
            $arParams["SHOW_ELEMENTS_CNT"] = false;
            $arResult["SHOW_ELEMENTS_CNT"] = $arParams["SHOW_ELEMENTS_CNT"];
        }

        // Максимальная глубина вложенности дерева
        if(!is_set($arParams["DEPTH"])) {
            $arParams["DEPTH"] = 100;
        }

        return $arParams;
    }

    /** @return array */
    protected function consistFilter()
    {
        $filter = [
            "IBLOCK_ID" => $this->arParams["IBLOCK_ID"]
        ];

        return $filter;
    }

    /**
     * Построение массива раздела с определенной глубиной вложенности
     *
     * @param array &$sectionArray Массив с разделом, с которого начинается вывод
     * @param int $currentDepth Текущий уровень вложенности (для рекурсии)
     * @param int $maxDepth Максимальный уровень вложенности
     */
    protected function buildSectionArray(&$sectionArray, $currentDepth, $maxDepth) {
        // Если есть дочерние категории и они не выходят за глубину вложенности
        if ($currentDepth + 1 > $maxDepth) unset($sectionArray["CHILDS"]);
        if (isset($sectionArray["CHILDS"])) {
            foreach ($sectionArray["CHILDS"] as $key => $childSection) {
                $this->buildSectionArray($childSection, $currentDepth + 1, $maxDepth);
            }
        }
    }

    /**
     * Формирование массива со всеми категориями (включая дочерние)
     *
     * @return array
     */
    function getSectionList()
    {
        // Запрашиваем информацию о пользовательских полях внутри раздела
        $this->getUserFields();

        // Список полей с файловыми значениями (эти поля нужно будет обновить, получив инфу о файлах)
        $this->arFieldsWithFileValues = array();
        // Список полей с привязками к элементам инф. блоков (эти поля нужно будет обновить, получив инфу о файлах)
        $this->arFieldsWithIBlockElementValues = array();

        // Формируем фильтр для выборки разделов
        $filter = $this->consistFilter();

        // Получаем список разделов
        $dbSection = CIBlockSection::GetList(
            Array(),
            $filter,
            true,
            array_merge($this->arParams["SELECT"], $this->arParams["USER_FIELDS"])
        );

        while($arSection = $dbSection->GetNext(true, false)) {
            $sectionId = $arSection['ID'];
            $parentSectionId = (int) $arSection['IBLOCK_SECTION_ID'];

            foreach($arSection as $key => $sectionField){
                if (substr($key, 0, 3) == "UF_") {
                    // Добавляем подмассив с пользовательским полем
                    $arSection[$key] = $this->arUserFields[$key];

                    // RAW_VALUE - "Сырое" значение, хранящее в базе.
                    // Для простых типов (таких как строки и числа) будет совпадать с VALUE
                    $arSection[$key]["RAW_VALUE"] = $sectionField;
                    $arSection[$key]["VALUE"] = $sectionField;

                    // Для поля типа "список" - запоминаем ссылку на данный элемент массива,
                    // чтобы позже получить инфу о перечисляемых значениях и проставить ее данному элементу (разделу)
                    if ($arSection[$key]["USER_TYPE_ID"] == "enumeration" && $arSection[$key]["VALUE"] != 0) {
                        $this->arFieldsWithEnumerationValues[] = &$arSection[$key];
                    }
                    // Для поля типа "файл" - запоминаем ссылку на данный элемент массива,
                    // чтобы позже получить инфу о файле и проставить ее данному элементу (разделу)
                    else if ($arSection[$key]["USER_TYPE_ID"] == "file" && $arSection[$key]["VALUE"] != 0) {
                        $this->arFieldsWithFileValues[] = &$arSection[$key];
                        $this->arFiles[$arSection[$key]["VALUE"]] = array();
                    }
                }
            }
            $arSections[$parentSectionId]['CHILDS'][$sectionId] = $arSection;
            $arSections[$sectionId] = &$arSections[$parentSectionId]['CHILDS'][$sectionId];
        }
        return array_shift($arSections);
    }

    /**
     * Проставление значений в полях типа "Файл"
     *
     * @return self
     */
    private function updateFileValues() {
        foreach ($this->arFieldsWithFileValues as &$field) {
            $field["VALUE"] =
                "upload/" .
                $this->arFiles[$field["RAW_VALUE"]]["SUBDIR"] .
                "/" .
                $this->arFiles[$field["RAW_VALUE"]]["FILE_NAME"];
            $field["FILE"] = $this->arFiles[$field["RAW_VALUE"]];
        }
        return $this;
    }

    /**
     * Проставление значений в полях типа "Список"
     *
     * @return self
     */
    private function updateEnumerationValues() {
        foreach ($this->arFieldsWithEnumerationValues as &$field) {
            $field["VALUE"] = $this->arUFListValues[$field["RAW_VALUE"]]["VALUE"];
        }
        return $this;
    }

    /**
     * Получение информации о файлах
     *
     * @return self
     */
    protected function getFileValues() {
        // Получаем информацию о файлах
        $dbRes = \CFile::GetList([],["@ID" => implode(',', array_keys($this->arFiles))]);
        // Формируем массив с инфо о файлах (ключ = id файла)
        $this->arFiles = array();
        while($file = $dbRes->GetNext(true, false)) {
            $this->arFiles[$file["ID"]] = $file;
        }
        return $this;
    }

    /**
     * Получение всех значений пользовательских полей с типом "список"
     *
     * @return self
     */
    protected function getUserTypeListValues() {
        $this->arUFListValues = array();
        $obEnum = new CUserFieldEnum;
        $rsEnum = $obEnum->GetList(array(), array());
        while($arEnum = $rsEnum->GetNext()){
            $this->arUFListValues[$arEnum["ID"]] = $arEnum;
        }
        // Передадим все значения в результирующий массив
        $this->arResult["UF_LIST_VALUES"] = $this->arUFListValues;
        return $this;
    }

    /**
     * Получение типов пользовательских полей
     *
     * @return self
     */
    protected function getUserFields() {
        $this->arUserFields = array();
        $userTypes = CUserTypeEntity::GetList(array(), array("ENTITY_ID" => "IBLOCK_" . $this->arParams["IBLOCK_ID"] . "_SECTION"));
        while ($userType = $userTypes->Fetch()) {
            $this->arUserFields[$userType["FIELD_NAME"]] = $userType;
        }
        return $this;
    }

    /**
     * Извлечение искомого раздела в массиве разделов
     *
     * @param array $sectionArray
     * @param string $fieldName
     * @param string|int $sectorValue
     * @return array|null
     */
    protected function extractSectionFromArray($sectionArray, $fieldName, $sectorValue) {
        if (isset($sectionArray[$fieldName]) && $sectionArray[$fieldName] == $sectorValue) {
            return $sectionArray;
        }
        // Если есть дочерние категории
        if (isset($sectionArray["CHILDS"])) {
            foreach ($sectionArray["CHILDS"] as $childSection) {
                $foundSection = $this->extractSectionFromArray($childSection, $fieldName, $sectorValue);
                // Если раздел был найден - возвращаем его
                if (isset($foundSection)) {
                    return $foundSection;
                }
            }
        }
        return null;
    }

}