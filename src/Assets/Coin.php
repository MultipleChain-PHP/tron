<?php

declare(strict_types=1);

namespace MultipleChain\Tron\Assets;

use MultipleChain\Utils\Number;
use MultipleChain\Tron\Provider;
use MultipleChain\Enums\ErrorType;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Assets\CoinInterface;
use MultipleChain\Tron\Services\TransactionSigner;

class Coin implements CoinInterface
{
    /**
     * @var Provider
     */
    private Provider $provider;

    /**
     * @param Provider|null $provider
     */
    public function __construct(?ProviderInterface $provider = null)
    {
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Tron';
    }

    /**
     * @return string
     */
    public function getSymbol(): string
    {
        return 'TRX';
    }

    /**
     * @return int
     */
    public function getDecimals(): int
    {
        return 6;
    }

    /**
     * @param string $owner
     * @return Number
     */
    public function getBalance(string $owner): Number
    {
        return new Number($this->provider->tron->getBalance($owner, true), $this->getDecimals());
    }

    /**
     * @param string $sender
     * @param string $receiver
     * @param float $amount
     * @return TransactionSigner
     */
    public function transfer(string $sender, string $receiver, float $amount): TransactionSigner
    {
        if ($amount < 0) {
            throw new \RuntimeException(ErrorType::INVALID_AMOUNT->value);
        }

        if ($amount > $this->getBalance($sender)->toFloat()) {
            throw new \RuntimeException(ErrorType::INSUFFICIENT_BALANCE->value);
        }

        if (strtolower($sender) === strtolower($receiver)) {
            throw new \RuntimeException(ErrorType::INVALID_ADDRESS->value);
        }

        try {
            $builder = $this->provider->tron->getTransactionBuilder();
            return new TransactionSigner($builder->sendTrx($receiver, $amount, $sender));
        } catch (\Exception $e) {
            throw new \RuntimeException(ErrorType::TRANSACTION_CREATION_FAILED->value . ": " . $e->getMessage());
        }
    }
}
