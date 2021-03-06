<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

global $OipSocialStoreCart;

?>

<!DOCTYPE html>
<html xml:lang="<?=LANGUAGE_ID?>" lang="<?=LANGUAGE_ID?>"  prefix="og: http://ogp.me/ns#" xmlns:fb="http://ogp.me/ns/fb#">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, width=device-width">

    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />

    <?$APPLICATION->ShowHead();?>

    <title><?$APPLICATION->ShowTitle()?></title>

    <?
        // Вендорные стили и скрипты сюда: local/src/webpack_vendor.js
        // Кастомные -- сюда: local/src/webpack_custom.js
    ?>

    <?require_once($_SERVER['DOCUMENT_ROOT'] . '/build/getWebpackAssets.php');?>
    <?if ($GLOBALS['VENDOR_STYLES'] !== ''):?>
        <link rel="stylesheet" href="/local/dist/<?=$GLOBALS['VENDOR_STYLES'];?>">
    <?endif;?>
    <?if ($GLOBALS['CUSTOM_STYLES'] !== ''):?>
        <link rel="stylesheet" href="/local/dist/<?=$GLOBALS['CUSTOM_STYLES'];?>">
    <?endif;?>
</head>

<body>

<?
$userId = $OipSocialStoreCart->getUserId();
?>
<span id="shopuserid" data-userid="<?=$userId?>"></span>

<nav class="uk-navbar-container" uk-navbar>
    <div class="uk-navbar-left">

        <ul class="uk-navbar-nav">
            <li><a href="/catalog/">Каталог</a></li>
            <li><a href="/cart/">Корзина</a></li>
            <li><a href="/shop/orders/">Заказы</a></li>
        </ul>

    </div>
    <div class="uk-navbar-right">
        <?if($USER->IsAuthorized()):?>
            <a href="?logout=yes">Выйти</a>
        <?endif?>
        <?$APPLICATION->IncludeComponent("oip:social.store.cart.widget","",[])?>
    </div>
</nav>
