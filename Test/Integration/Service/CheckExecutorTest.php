<?php

namespace MageSuite\SalesMonitoring\Test\Integration\Service;

/**
 * @magentoAppIsolation enabled
 */
class CheckExecutorTest extends \PHPUnit\Framework\TestCase
{
    const PAYMENT_METHOD_A = 'pma';
    const PAYMENT_METHOD_B = 'pmb';

    const SHIPPING_METHOD_A = 'cc_sma';
    const SHIPPING_METHOD_B = 'cc_smb';

    /**
     * @var \MageSuite\SalesMonitoring\Service\CheckExecutor
     */
    private $executor;

    /**
     * @var \MageSuite\SalesMonitoring\Repository\CheckRepository
     */
    private $repository;

    public function setUp()
    {
        $this->executor = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create(\MageSuite\SalesMonitoring\Service\CheckExecutor::class);

        $this->repository = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create(\MageSuite\SalesMonitoring\Repository\CheckRepository::class);
    }

    /**
     * @magentoDataFixture loadOrders
     * @magentoDbIsolation enabled
     */
    public function testThatvalidChecksAreProcessed()
    {
        $startTime = new \DateTime();
        $this->executor->executeAll();

        /** @var \MageSuite\SalesMonitoring\Model\Check $check */
        foreach ($this->repository->getAll() as $check) {
            if (false !== stripos($check->getName(), 'outside')) {
                /* Skip checks that are purposefully oustide the current time */
                continue;
            }
            
            $this->assertGreaterThanOrEqual($startTime, $check->getExecutedAt());
        }
    }

    /**
     * @magentoDataFixture loadOrders
     * @magentoDbIsolation enabled
     */
    public function testThatGlobalCheckIsNotTriggered()
    {
        $startTime = new \DateTime();
        $this->executor->executeAll();

        /** @var \MageSuite\SalesMonitoring\Model\Check $check */
        $check = current($this->repository->findByName('All 3h'));

        $this->assertTrue($check->isInOKState());
        $this->assertGreaterThanOrEqual($startTime, $check->getExecutedAt());
    }

    /**
     * @magentoDataFixture loadOrders
     * @magentoDbIsolation enabled
     */
    public function testThatCheckWithShippingMethodThatHasNoOrdersIsTriggered()
    {
        $startTime = new \DateTime();
        $this->executor->executeAll();

        /** @var \MageSuite\SalesMonitoring\Model\Check $check */
        $check = current($this->repository->findByName('Shipping Method No Orders'));

        $this->assertTrue($check->isInAlarmState());
        $this->assertGreaterThanOrEqual($startTime, $check->getTriggeredAt());
    }

    /**
     * @magentoDataFixture loadOrders
     * @magentoDbIsolation enabled
     */
    public function testThatCheckWithPaymentMethodThatHasNoOrdersIsTriggered()
    {
        $startTime = new \DateTime();
        $this->executor->executeAll();

        /** @var \MageSuite\SalesMonitoring\Model\Check $check */
        $check = current($this->repository->findByName('Payment Method No Orders'));

        $this->assertTrue($check->isInAlarmState());
        $this->assertGreaterThanOrEqual($startTime, $check->getTriggeredAt());
    }

    /**
     * @magentoDataFixture loadOrders
     * @magentoDbIsolation enabled
     */
    public function testThatCheckWithShppingAndPaymentIsOkay()
    {
        $startTime = new \DateTime();
        $this->executor->executeAll();

        /** @var \MageSuite\SalesMonitoring\Model\Check $check */
        $check = current($this->repository->findByName('Shipping and payment'));

        $this->assertTrue($check->isInOKState());
        $this->assertGreaterThanOrEqual($startTime, $check->getExecutedAt());
    }

    /**
     * @magentoDataFixture loadOrders
     * @magentoDbIsolation enabled
     */
    public function testThatCheckWithOutsideHoursIsNotProcessed()
    {
        $this->executor->executeAll();

        /** @var \MageSuite\SalesMonitoring\Model\Check $check */
        $check = current($this->repository->findByName('Outside hours'));

        $this->assertNull($check->getExecutedAt());
        $this->assertFalse($check->isInAlarmState());
    }

    /**
     * @magentoDataFixture loadOrders
     * @magentoDbIsolation enabled
     */
    public function testThatCheckWithOutsideDaysIsNotProcessed()
    {
        $this->executor->executeAll();

        /** @var \MageSuite\SalesMonitoring\Model\Check $check */
        $check = current($this->repository->findByName('Outside days'));

        $this->assertNull($check->getExecutedAt());
        $this->assertFalse($check->isInAlarmState());
    }

    public static function loadOrders()
    {
        include __DIR__ . '/../../_files/orders_sparse.php';
        include __DIR__ . '/../../_files/checks.php';
    }
}