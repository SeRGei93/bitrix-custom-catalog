<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\CBitrixComponent::includeComponentClass("oip:component");

use Bitrix\Main\Application;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\SystemException;

use Oip\SocialStore\Cart\Handler as Cart;
use Oip\SocialStore\Cart\Repository\RepositoryInterface;
use Oip\SocialStore\Cart\Repository\DBRepository as CartRepository;

use Oip\Util\Bitrix\Iblock\ElementPath\Helper as PathHelper;

use Oip\Util\Collection\Factory\CollectionsFactory as ProductsFactory;
use Oip\Util\Collection\Factory\InvalidSubclass as InvalidSubclassException;
use Oip\Util\Collection\Factory\NonUniqueIdCreating as NonUniqueIdCreatingException;

use Oip\GuestUser\Handler as GuestUser;
use Oip\SocialStore\User\Entity\User as StoreUser;
use Oip\SocialStore\User\Repository\UserRepository;
use Oip\Util\Bitrix\DateTimeConverter;
use Oip\SocialStore\User\Repository\NotFoundException;

abstract class COipSocialStoreCart extends \COipComponent {

    /**
     * @return Cart
     * @throws InvalidSubclassException
     * @throws SystemException
     * @throws NonUniqueIdCreatingException
     */
    public function executeComponent()
    {
        return $this->getCart();
    }

    /**
     * @return Cart
     * @throws InvalidSubclassException
     * @throws SystemException
     * @throws NonUniqueIdCreatingException
     */
    protected function getCart() {

        try {
            $repository = $this->initCartRepository();
            $userId = $this->initCartUser();
            $cart = $this->initCart($userId, $repository);
            $cart->getProducts();

            return $cart;
        }
        catch(SqlQueryException $exception) {
            global $APPLICATION;
            $APPLICATION->IncludeComponent("oip:system.exception.viewer","",[
                "EXCEPTION" => $exception
            ]);
        }

    }

    /**
     * @return RepositoryInterface
     * @throws SystemException
     */
    private function initCartRepository(): RepositoryInterface {
        $connection = Application::getConnection();
        $catalogIblockId = (int)Configuration::getValue("oip_catalog_iblock_id");
        if(!$catalogIblockId) {
            throw new SystemException("Invalid or non-existing config variable \"oip_catalog_iblock_id\". Check if it exists.");
        }

        $pathHelper = new PathHelper($connection, $catalogIblockId);

        return new CartRepository($connection, $pathHelper);
    }

    /**
     * @return int
     * @throws SqlQueryException
     */
    private function initCartUser(): int {

        global $USER;
        global $OipGuestUser;

        if($USER->IsAuthorized()) {
            $storeUser = $this->initStoreUser();
            $userId = $storeUser->getId();
        }
        else {
            /** @var $OipGuestUser GuestUser */

            try {
                $userId = $OipGuestUser->getUser()->getNegativeId();
            }
            catch(SqlQueryException $exception) {
                global $APPLICATION;
                $APPLICATION->IncludeComponent("oip:system.exception.viewer","",[
                    "EXCEPTION" => $exception
                ]);
            }

        }

        return (int)$userId;
    }

    /**
     * @param int $userId
     * @param RepositoryInterface $repository
     * @return Cart
     *
     * @throws InvalidSubclassException
     * @throws NonUniqueIdCreatingException
     * @throws Exception
     */
    private function initCart(int $userId, RepositoryInterface $repository): Cart {
        $products = ProductsFactory::createByObjects([], "Oip\SocialStore\Product\Entity\ProductCollection");
        return new Cart($userId, $products, $repository, Oip\App::getPriceProvider());
    }

    /**
     * @return Cart|null
    */
    protected function getProcessorResult(): ?Cart {
        global $OipSocialStoreCart;

        if(!is_set($OipSocialStoreCart) || !($OipSocialStoreCart instanceof Cart)) {
            $this->arResult["EXCEPTION"] = "The component oip:social.store.cart.processor wasn't running";
            return null;
        }
        else {
            return $OipSocialStoreCart;
        }
    }

    /**
     * @return StoreUser
     * @throws SqlQueryException
     */
    private function initStoreUser(): StoreUser {
        global $USER;
        $bxUser  = $USER->GetByID($USER->GetID())->arResult[0];

        $userRepository = new UserRepository(Application::getConnection(), new DateTimeConverter());

        try {
            $storeUser = $userRepository->getByBxId((int)$USER->GetID());
        }
        catch (NotFoundException $exception) {
            $newStoreUserId = $userRepository->addFromBxUser(
                $bxUser["EMAIL"],
                $bxUser["PERSONAL_PHONE"],
                (int)$USER->GetID(),
                $bxUser["NAME"],
                $bxUser["LAST_NAME"],
                $bxUser["SECOND_NAME"],
            );
            $userRepository->verifyUserPhone($newStoreUserId);

            $storeUser = $userRepository->getById($newStoreUserId);
        }
        finally {
            return $storeUser;
        }
    }
}