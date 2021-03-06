<?php

namespace Oip\Custom\Component\Iblock;

class Section
{
    /** @var int $id */
    private $id;
    /** @var int $iblockId */
    private $iblockId;
    /** @var int $iblockSectionId */
    private $iblockSectionId;
    /** @var string $active */
    private $active;
    /** @var string $code */
    private $code;
    /** @var int $sort */
    private $sort;
    /** @var string $name */
    private $name;
    /** @var string $description */
    private $description;
    /** @var string $sectionPageUrl */
    private $sectionPageUrl;
    /** @var string $picture */
    private $picture;
    /** @var string $detailPicture */
    private $detailPicture;
    /** @var UFProperty[] $props */
    private $props;
    /** @var Section[] $subSections */
    private $subSections;

    public function __construct($data)
    {
        $this->id = $data["ID"];
        $this->iblockId = $data["IBLOCK_ID"];
        $this->iblockSectionId = $data["IBLOCK_SECTION_ID"];
        $this->code = $data["CODE"];
        $this->name = $data["NAME"];
        $this->description = $data["DESCRIPTION"];
        $this->sort = $data["SORT"];
        $this->active = $data["ACTIVE"];
        $this->sectionPageUrl = $data["SECTION_PAGE_URL"];
        // array_shift, т.к. в VALUE для "PICTURE" / "DETAIL_PICTURE" будет всего один элемент - файл (изображение)
        $this->picture = array_shift($data["PICTURE"]["VALUE"]);
        $this->detailPicture = array_shift($data["DETAIL_PICTURE"]["VALUE"]);

        // Заполняем пользовательские поля
        $props = [];
        foreach($data as $propCode => $arProp) {
            // Выбираем пользовательские поля
            if (substr($propCode, 0, 3) == "UF_") {
                $props[$propCode] = new UFProperty($arProp);
            }
        }
        $this->props = $props;

        // Если есть дочерние категории - инициализируем их и привязываем к текущему разделу
        if (isset($data["CHILDS"])) {
            foreach ($data["CHILDS"] as $child) {
                $this->subSections[] = new Section($child);
            }
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getIblockId()
    {
        return $this->iblockId;
    }

    /**
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @return string
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @return string
     */
    public function getSectionPageUrl()
    {
        return $this->sectionPageUrl;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return ($this->getActive() === "Y");
    }

    /**
     * @return int
     */
    public function getIblockSectionId()
    {
        return $this->iblockSectionId;
    }


    /** @return UFProperty[] */
    public function getProps() {
        return $this->props;
    }

    /**
     * @var string $propCode
     * @return UFProperty|null
     */
    public function getProp($propCode) {
        return $this->getProps()[$propCode];
    }

    /**
     * @param string $propCode
     * @return string|array
     */
    public function getPropValue($propCode) {
        return ($this->getProps()[$propCode]) ? $this->getProps()[$propCode]->getValue() : null;
    }

    /**
     * @return Section[]
     */
    public function getSubSections() {
        return $this->subSections;
    }

    /**
     * @return string
     */
    public function getPictureUrl() {
        return isset($this->picture) ? "/upload/" . $this->picture["SUBDIR"] . "/" . $this->picture["FILE_NAME"] : null;
    }

    /**
     * @return string
     */
    public function getDetailPictureUrl() {
        return isset($this->detailPicture) ? "/upload/" . $this->detailPicture["SUBDIR"] . "/" . $this->detailPicture["FILE_NAME"] : null;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }
}