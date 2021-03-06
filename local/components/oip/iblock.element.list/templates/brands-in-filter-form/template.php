<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arResult */
/** @var $this \CBitrixComponentTemplate */
/** @var $component \COipIblockElementList */
/** @var \Oip\Custom\Component\Iblock\Element $element */
/** @var \Oip\Custom\Component\Iblock\Element[] $elements */

$component = $this->getComponent();
$exception = $arResult["EXCEPTION"];
$errors = $arResult["ERRORS"];
$elements = $arResult["ELEMENTS"];
$filterId = $component->getParam("FILTER_ID");
$arFilterParams = $component->getParam("FILTER_PARAMS");

$isBrandsSelected = array_key_exists("f".$filterId."_pBRANDS",$arFilterParams);

if($isBrandsSelected) {
    $selectedCount = count($arFilterParams["f".$filterId."_pBRANDS"]);
}

if($selectedCount == 1) {
    $selectedName = $elements[reset($arFilterParams["f".$filterId."_pBRANDS"])]->getName();
}
?>

<style>
    .oip-filter-brands-container.uk-nav > li >  a {
        color: #999;
    }
    .oip-filter-brands-container.uk-nav > li >  a:hover,
    .oip-filter-brands-container.uk-nav > li >  a:focus
    {
        color: #666;
    }
    .oip-filter-brands-container.uk-nav .uk-checkbox {
       background-color: transparent;
        border: 1px solid #ccc;
    }
    .oip-filter-brands-container.uk-nav .uk-checkbox:focus {
        outline: 0;
        border-color: #1e87f0;
    }

    .oip-filter-brands-container.uk-nav .uk-checkbox:checked {
        background-color: #1e87f0;
        border-color: transparent;
    }

    .oip-filter-brands-container.uk-nav .uk-checkbox:checked:focus {
        background-color: #0e6dcd;
    }
</style>

<?if($exception):?>
    <p><?=$exception?></p>
<?else:?>

    <?if($errors):?>
        <?foreach($errors as $error):?>
            <p><?=$error?></p>
        <?endforeach?>
    <?endif?>

    <input type="hidden" name="data-form-filter-id" id="data-form-filter-id" value="<?=$filterId?>">

    <?if($elements):?>
        <div class="uk-inline">

            <button class="uk-button uk-button-default uk-button-small uk-text-lowercase" type="button">
                <i class="uk-margin-small-right" uk-icon="bookmark"></i>

                <?if(!$isBrandsSelected):?>
                    Выберите бренд
                <?elseif($selectedCount == 1):?>
                    <?=$selectedName?>
                <?else:?>
                    <?=$selectedCount?> <?=$component->getNumWord($selectedCount, ["бренд","бренда","брендов"])?>
                <?endif?>
            </button>

            <div uk-dropdown="mode: click">
                <form>
                    <ul class="uk-nav uk-dropdown-nav oip-filter-brands-container" id="oip-filter-brands-container">
                         <?foreach($elements as $element):?>

                            <?
                                $isActive = (
                                        array_key_exists("f".$filterId."_pBRANDS",$arFilterParams)
                                        && in_array($element->getId(),$arFilterParams["f".$filterId."_pBRANDS"])
                                ) ? true : false;
                             ?>

                            <li <?if($isActive):?>class="uk-active"<?endif?>>
                                <a href="javascript:void(0);">
                                    <label><input class="uk-checkbox oip-filter-brand-item" type="checkbox"
                                                  name="f<?=$filterId?>_pBRANDS_<?=$element->getId()?>"
                                                  <?if($isActive):?>checked<?endif?>
                                        >
                                        <?=$element->getName()?>
                                    </label>
                                </a>
                            </li>
                         <?endforeach?>
                    </ul>
                </form>
            </div>

        </div>
    <?endif?>


<?endif?>
