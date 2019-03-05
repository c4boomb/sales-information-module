<?php

namespace Lev\SalesInformation\Model;

use Lev\SalesInformation\Api\Data\SalesInformationInterface;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;

/**
 * Class DataObjectSalesInformation
 *
 * @package Lev\SalesInformation\Model
 */
class SalesInformation implements SalesInformationInterface
{
    /**
     * @var int
     */
    protected $productId;

    /**
     * @var OrderItemCollectionFactory
     */
    protected $orderItemCollectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var string
     */
    protected $orderStatus;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * SalesInformation constructor
     *
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param int $productId
     * @param string $orderStatus
     */
    public function __construct(
        OrderItemCollectionFactory $orderItemCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        SortOrderBuilder $sortOrderBuilder,
        OrderRepositoryInterface $orderRepository,
        int $productId,
        string $orderStatus = 'complete'
    ) {
        $this->productId = $productId;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->orderRepository = $orderRepository;
        $this->orderStatus = $orderStatus;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * Get qty of orders with this product
     *
     * @return int
     */
    public function getQty(): int
    {
        /** Using collections to increase speed */
        $orderItemCollection = $this->orderItemCollectionFactory->create();

        $orderItemCollection->addAttributeToFilter(OrderItemInterface::PRODUCT_ID, $this->productId)
            ->addAttributeToSelect(OrderItemInterface::ORDER_ID)
            ->addAttributeToSelect(OrderItemInterface::QTY_ORDERED);
        $orderItemCollection->getSelect()
            ->joinLeft(
                ["so" => $orderItemCollection->getTable('sales_order')],
                sprintf("so.%s = main_table.%s", OrderInterface::ENTITY_ID, OrderItemInterface::ORDER_ID)
            )->where(
                sprintf("so.%s = '%s'", OrderInterface::STATUS, $this->orderStatus)
            );

        $qty = $orderItemCollection->getColumnValues(OrderItemInterface::QTY_ORDERED);

        /** Summing up all qty */
        return (int) array_reduce($qty, function ($carry, $item) {
            $carry += $item;
            return $carry;
        });
    }

    /**
     * Get last order with this product
     *
     * @return string
     */
    public function getLastOrder(): string
    {
        $orderItemCollection = $this->orderItemCollectionFactory->create();

        $orderItemCollection
            ->addAttributeToFilter(OrderItemInterface::PRODUCT_ID, $this->productId)
            ->addAttributeToSelect(OrderItemInterface::ORDER_ID);
        $orderItemCollection->getSelect()->group(OrderItemInterface::ORDER_ID);

        $orderIds = $orderItemCollection->getColumnValues(OrderItemInterface::ORDER_ID);

        $orderIdFilter = $this->filterBuilder
            ->setField(OrderInterface::ENTITY_ID)
            ->setValue($orderIds)
            ->setConditionType("in")
            ->create();
        $dateSortOrder = $this->sortOrderBuilder
            ->setField(OrderInterface::CREATED_AT)
            ->setDescendingDirection()
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilters([$orderIdFilter])
            ->addSortOrder($dateSortOrder)
            ->setPageSize(1)
            ->setCurrentPage(1)
            ->create();

        $results = $this->orderRepository->getList($searchCriteria)->getItems();

        if (empty($results)) {
            return '';
        }

        /** In other cases there would be only 1 result as PageSize is 1 */
        return (string) array_pop($results)->getCreatedAt();
    }
}
