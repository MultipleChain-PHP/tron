<?php

declare(strict_types=1);

namespace MultipleChain\Tron\Tests\Models;

use MultipleChain\Enums\TransactionType;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Tron\Tests\BaseTest;
use MultipleChain\Tron\Models\Transaction;

class TransactionTest extends BaseTest
{
    /**
     * @var Transaction
     */
    private Transaction $tx;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->tx = new Transaction($this->data->coinTransferTx);
    }

    /**
     * @return void
     */
    public function testId(): void
    {
        $this->assertEquals($this->data->coinTransferTx, $this->tx->getId());
    }

    /**
     * @return void
     */
    public function testData(): void
    {
        $this->assertIsArray($this->tx->getData());
    }

    /**
     * @return void
     */
    public function testType(): void
    {
        $this->assertEquals(TransactionType::COIN, $this->tx->getType());
    }

    /**
     * @return void
     */
    public function testWait(): void
    {
        $this->assertEquals(TransactionStatus::CONFIRMED, $this->tx->wait());
    }

    /**
     * @return void
     */
    public function testUrl(): void
    {
        $this->assertEquals(
            'https://nile.tronscan.org/#/transaction/8697ad2c4e1713227c16a65a5845636458df2d3db3adf526e07e17699bc6b3c4',
            $this->tx->getUrl()
        );
    }

    /**
     * @return void
     */
    public function testSender(): void
    {
        $this->assertEquals(strtolower($this->data->modelTestSender), strtolower($this->tx->getSigner()));
    }

    /**
     * @return void
     */
    public function testFee(): void
    {
        $this->assertEquals(1.1, $this->tx->getFee()->toFloat());
    }

    /**
     * @return void
     */
    public function testBlockNumber(): void
    {
        $this->assertEquals(46506377, $this->tx->getBlockNumber());
    }

    /**
     * @return void
     */
    public function testBlockTimestamp(): void
    {
        $this->assertEquals(1714619148, $this->tx->getBlockTimestamp());
    }

    /**
     * @return void
     */
    public function testBlockConfirmationCount(): void
    {
        $this->assertGreaterThan(199, $this->tx->getBlockConfirmationCount());
    }

    /**
     * @return void
     */
    public function testStatus(): void
    {
        $this->assertEquals(TransactionStatus::CONFIRMED, $this->tx->getStatus());
    }
}
