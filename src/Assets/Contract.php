<?php

declare(strict_types=1);

namespace MultipleChain\Tron\Assets;

use MultipleChain\Tron\Provider;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Assets\ContractInterface;

class Contract implements ContractInterface
{
    /**
     * @var string
     */
    private string $address;

    /**
     * @var Provider
     */
    private Provider $provider;

    /**
     * @param string $address
     * @param Provider|null $provider
     */
    public function __construct(string $address, ?ProviderInterface $provider = null)
    {
        $this->address = $address;
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    public function callMethod(string $method, mixed ...$args): mixed
    {
        $this->provider->isTestnet(); // just for phpstan
        return 'example';
    }

    /**
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    public function getMethodData(string $method, mixed ...$args): mixed
    {
        return 'example';
    }

    /**
     * @param string $method
     * @param string $from
     * @param mixed ...$args
     * @return mixed
     */
    public function createTransactionData(string $method, string $from, mixed ...$args): mixed
    {
        return 'example';
    }
}
