<?php

declare(strict_types=1);

namespace MultipleChain\Tron\Services;

use MultipleChain\Tron\Provider;
use MultipleChain\Enums\ErrorType;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Services\TransactionSignerInterface;

class TransactionSigner implements TransactionSignerInterface
{
    /**
     * @var array<mixed>
     */
    private array $rawData;

    /**
     * @var array<mixed>
     */
    private array $signedData;

    /**
     * @var Provider
     */
    private Provider $provider;

    /**
     * @param mixed $rawData
     * @param Provider|null $provider
     * @return void
     */
    public function __construct(mixed $rawData, ?ProviderInterface $provider = null)
    {
        $this->rawData = $rawData;
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @param string $privateKey
     * @return TransactionSignerInterface
     */
    public function sign(string $privateKey): TransactionSignerInterface
    {
        try {
            $this->provider->tron->setPrivateKey($privateKey);
            $this->signedData = $this->provider->tron->signTransaction($this->rawData);
            return $this;
        } catch (\Throwable $th) {
            throw new \RuntimeException(ErrorType::TRANSACTION_CREATION_FAILED->value . ": " . $th->getMessage());
        }
    }

    /**
     * @return string Transaction id
     */
    public function send(): string
    {
        try {
            $result = $this->provider->tron->sendRawTransaction($this->signedData);
            if (!isset(($result ?: [])['result'])) {
                $message = $result['message'] ?: null;
                $message = is_null($message) ? null : hex2bin($message);
                throw new \RuntimeException($message ?? ErrorType::TRANSACTION_CREATION_FAILED->value);
            }
            return $result['txid'];
        } catch (\Throwable $th) {
            throw new \RuntimeException(ErrorType::TRANSACTION_CREATION_FAILED->value . ": " . $th->getMessage());
        }
    }

    /**
     * @return  array<mixed>
     */
    public function getRawData(): array
    {
        return $this->rawData;
    }

    /**
     * @return array<mixed>
     */
    public function getSignedData(): array
    {
        return $this->signedData;
    }
}
