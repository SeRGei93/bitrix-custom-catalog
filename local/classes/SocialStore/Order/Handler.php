<?php

namespace Oip\SocialStore\Order;

use Oip\SocialStore\Order\Entity\Order;
use Oip\SocialStore\Order\Repository\RepositoryInterface as OrderRepository;
use Oip\SocialStore\Order\Status\Repository\RepositoryInterface as StatusRepository;
use Oip\SocialStore\Order\Entity\OrderCollection;

class Handler
{
    /** @var OrderRepository $orderRepository */
    private $orderRepository;
    /** @var StatusRepository $statusRepository */
    private $statusRepository;

    public function __construct(OrderRepository $orderRepository, StatusRepository $statusRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->statusRepository = $statusRepository;
    }

    public function getById(int $orderId): Order {
        return $this->orderRepository->getById($orderId);
    }


    public function getAllByUserId(int $userId, $page = 1, $onPage = null): OrderCollection {
        return $this->orderRepository->getAllByUserId($userId, $page, $onPage);
    }

    public function getCountByUserId(int $userId): int {
        return $this->orderRepository->getCountByUserId($userId);
    }

    public function addOrder(Order $order): Order {
        $insertedId = $this->orderRepository->addOrder($order);
        return $this->getById($insertedId);
    }

    public function removeOrder(int $orderId): int {
        return $this->orderRepository->removeOrder($orderId);
    }

    public function updateOrderStatus(Order $order, string $statusCode): Order {
        $orderId = $order->getId();
        $statusId = $this->statusRepository->getByCode($statusCode)->getId();

        $this->orderRepository->updateStatus($orderId, $statusId);

        return $this->orderRepository->getById($orderId);
    }
}