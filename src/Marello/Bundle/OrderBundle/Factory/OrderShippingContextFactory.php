<?php

namespace Marello\Bundle\OrderBundle\Factory;

use Marello\Bundle\AddressBundle\Entity\MarelloAddress;
use Marello\Bundle\InventoryBundle\Entity\Warehouse;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Marello\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Marello\Bundle\OrderBundle\Entity\Order;
use Marello\Bundle\ShippingBundle\Context\ShippingContextFactoryInterface;
use Marello\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Marello\Bundle\ShippingBundle\Context\ShippingContextInterface;

class OrderShippingContextFactory implements ShippingContextFactoryInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var OrderShippingLineItemConverterInterface
     */
    private $shippingLineItemConverter;

    /**
     * @var ShippingContextBuilderFactoryInterface|null
     */
    private $shippingContextBuilderFactory;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param OrderShippingLineItemConverterInterface $shippingLineItemConverter
     * @param null|ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        OrderShippingLineItemConverterInterface $shippingLineItemConverter,
        ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory = null
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->shippingLineItemConverter = $shippingLineItemConverter;
        $this->shippingContextBuilderFactory = $shippingContextBuilderFactory;
    }

    /**
     * @param Order $order
     * @return ShippingContextInterface
     */
    public function create($order)
    {
        $this->ensureApplicable($order);

        if (null === $this->shippingContextBuilderFactory) {
            return null;
        }

        $shippingContextBuilder = $this->shippingContextBuilderFactory->createShippingContextBuilder(
            $order,
            (string)$order->getId()
        );

        $subtotal = Price::create(
            $order->getSubtotal(),
            $order->getCurrency()
        );

        $shippingContextBuilder
            ->setSubTotal($subtotal)
            ->setCurrency($order->getCurrency());

        $convertedLineItems = $this->shippingLineItemConverter->convertLineItems($order->getItems());
        $shippingOrigin = $this->getShippingOrigin();

        if (null !== $shippingOrigin) {
            $shippingContextBuilder->setShippingOrigin($shippingOrigin);
        }

        if (null !== $order->getShippingAddress()) {
            $shippingContextBuilder->setShippingAddress($order->getShippingAddress());
        }

        if (null !== $order->getBillingAddress()) {
            $shippingContextBuilder->setBillingAddress($order->getBillingAddress());
        }

        if (null !== $order->getCustomer()) {
            $shippingContextBuilder->setCustomer($order->getCustomer());
        }

        if (null !== $convertedLineItems) {
            $shippingContextBuilder->setLineItems($convertedLineItems);
        }

        return $shippingContextBuilder->getResult();
    }

    /**
     * @param object $entity
     * @throws \InvalidArgumentException
     */
    protected function ensureApplicable($entity)
    {
        if (!is_a($entity, Order::class)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" expected, "%s" given',
                Order::class,
                is_object($entity) ? get_class($entity) : gettype($entity)
            ));
        }
    }

    /**
     * @return MarelloAddress|null
     */
    protected function getShippingOrigin()
    {
        /** @var Warehouse $warehouse */
        $warehouse = $this->doctrineHelper
            ->getEntityManagerForClass(Warehouse::class)
            ->getRepository(Warehouse::class)
            ->getDefault();
        
        return $warehouse->getAddress();
    }
}
