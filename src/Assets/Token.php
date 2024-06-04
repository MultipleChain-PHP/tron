<?php

declare(strict_types=1);

namespace MultipleChain\Tron\Assets;

use MultipleChain\Utils;
use MultipleChain\Utils\Number;
use MultipleChain\Tron\Provider;
use MultipleChain\Enums\ErrorType;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Tron\Services\TransactionSigner;
use MultipleChain\Interfaces\Assets\TokenInterface;

class Token extends Contract implements TokenInterface
{
    /**
     * @param string $address
     * @param Provider|null $provider
     * @param array<object>|null $abi
     */
    public function __construct(string $address, ?ProviderInterface $provider = null, ?array $abi = null)
    {
        parent::__construct(
            $address,
            $provider,
            $abi ? $abi : json_decode(file_get_contents(dirname(__DIR__, 2) . '/resources/TRC20.json') ?: '')
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->callMethodWithCache('name');
    }

    /**
     * @return string
     */
    public function getSymbol(): string
    {
        return $this->callMethodWithCache('symbol');
    }

    /**
     * @return int
     */
    public function getDecimals(): int
    {
        return (int) hexdec($this->callMethodWithCache('decimals'));
    }

    /**
     * @param string $owner
     * @return Number
     */
    public function getBalance(string $owner): Number
    {
        $owner = $this->provider->tron->address2HexString($owner);
        $balance = $this->callMethod('balanceOf', $owner, ['from' => $owner]);
        return new Number($balance, $this->getDecimals());
    }

    /**
     * @return Number
     */
    public function getTotalSupply(): Number
    {
        $totalSupply = $this->callMethod('totalSupply');
        return new Number($totalSupply, $this->getDecimals());
    }

    /**
     * @param string $owner
     * @param string $spender
     * @return Number
     */
    public function getAllowance(string $owner, string $spender): Number
    {
        $owner = $this->provider->tron->address2HexString($owner);
        $spender = $this->provider->tron->address2HexString($spender);
        $allowance = $this->callMethod('allowance', $owner, $spender, ['from' => $owner]);
        return new Number($allowance, $this->getDecimals());
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

        $amount = Utils::numberToHex($amount, $this->getDecimals());
        $sender = $this->provider->tron->address2HexString($sender);
        $receiver = $this->provider->tron->address2HexString($receiver);

        try {
            return new TransactionSigner($this->triggerContract(
                $this->createTransactionData('transfer', $sender, $receiver, $amount)
            ));
        } catch (\Throwable $th) {
            throw new \RuntimeException($th->getMessage(), $th->getCode());
        }
    }

    /**
     * @param string $spender
     * @param string $owner
     * @param string $receiver
     * @param float $amount
     * @return TransactionSigner
     */
    public function transferFrom(
        string $spender,
        string $owner,
        string $receiver,
        float $amount
    ): TransactionSigner {
        if ($amount < 0) {
            throw new \RuntimeException(ErrorType::INVALID_AMOUNT->value);
        }

        if ($amount > $this->getBalance($owner)->toFloat()) {
            throw new \RuntimeException(ErrorType::INSUFFICIENT_BALANCE->value);
        }

        $allowance = $this->getAllowance($owner, $spender)->toFloat();
        if (0 == $allowance) {
            throw new \RuntimeException(ErrorType::UNAUTHORIZED_ADDRESS->value);
        }

        if ($amount > $allowance) {
            throw new \RuntimeException(ErrorType::INVALID_AMOUNT->value);
        }

        $amount = Utils::numberToHex($amount, $this->getDecimals());
        $owner = $this->provider->tron->address2HexString($owner);
        $spender = $this->provider->tron->address2HexString($spender);
        $receiver = $this->provider->tron->address2HexString($receiver);

        try {
            return new TransactionSigner($this->triggerContract(
                $this->createTransactionData('transferFrom', $spender, $owner, $receiver, $amount)
            ));
        } catch (\Throwable $th) {
            throw new \RuntimeException($th->getMessage(), $th->getCode());
        }
    }

    /**
     * @param string $owner
     * @param string $spender
     * @param float $amount
     * @return TransactionSigner
     */
    public function approve(string $owner, string $spender, float $amount): TransactionSigner
    {
        if ($amount < 0) {
            throw new \RuntimeException(ErrorType::INVALID_AMOUNT->value);
        }

        if ($amount > $this->getBalance($owner)->toFloat()) {
            throw new \RuntimeException(ErrorType::INSUFFICIENT_BALANCE->value);
        }

        $amount = Utils::numberToHex($amount, $this->getDecimals());
        $owner = $this->provider->tron->address2HexString($owner);
        $spender = $this->provider->tron->address2HexString($spender);

        try {
            return new TransactionSigner($this->triggerContract(
                $this->createTransactionData('approve', $owner, $spender, $amount)
            ));
        } catch (\Throwable $th) {
            throw new \RuntimeException($th->getMessage(), $th->getCode());
        }
    }
}
