<?php

namespace Lev\SalesInformation\Plugin;

use Lev\SalesInformation\Model\SalesInformationFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;

/**
 * Class SalesInformationAttribute
 *
 * @package Lev\SalesInformation\Plugin
 */
class SalesInformationAttribute
{
    /**
     * @var ExtensionAttributesFactory
     */
    private $extensionAttributesFactory;

    /**
     * @var SalesInformationFactory
     */
    private $salesInformationFactory;

    /**
     * SalesInformationAttribute constructor.
     * @param ExtensionAttributesFactory $extensionAttributesFactory
     * @param SalesInformationFactory $salesInformationFactory
     */
    public function __construct(
        ExtensionAttributesFactory $extensionAttributesFactory,
        SalesInformationFactory $salesInformationFactory
    ) {
        $this->extensionAttributesFactory = $extensionAttributesFactory;
        $this->salesInformationFactory = $salesInformationFactory;
    }

    /**
     * Add sales information to product after get
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $product
     * @return ProductInterface
     */
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ) {
        $extensionAttributes = $product->getExtensionAttributes();

        if (!$extensionAttributes) {
            $extensionAttributes = $this->extensionAttributesFactory->create(ProductInterface::class);
        }

        $salesInformation = $this->salesInformationFactory->create(["productId" => $product->getId()]);
        $extensionAttributes->setSalesInformation($salesInformation);

        $product->setExtensionAttributes($extensionAttributes);

        return $product;
    }
}
