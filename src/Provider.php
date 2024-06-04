<?php

declare(strict_types=1);

namespace MultipleChain\Tron;

use IEXBase\TronAPI\Tron;
use MultipleChain\Enums\ErrorType;
use IEXBase\TronAPI\Provider\HttpProvider;
use IEXBase\TronAPI\Exception\TronException;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\BaseNetworkConfig as NetworkConfig;

class Provider implements ProviderInterface
{
    /**
     * @var NetworkConfig
     */
    public NetworkConfig $network;

    /**
     * @var array<string,array<string>>
     */
    public array $nodes = [
        'mainnet' => [
            'id' => '0x2b6653dc',
            'node' => 'mainnet',
            'name' => 'TronGrid Mainnet',
            'host' => 'https://api.trongrid.io',
            'event' => 'https://api.trongrid.io',
            'explorer' => 'https://tronscan.org/'
        ],
        'testnet' => [
            'id' => '0xcd8690dc',
            'node' => 'testnet',
            'name' => 'TronGrid Nile Testnet',
            'host' => 'https://nile.trongrid.io',
            'event' => 'https://event.nileex.io',
            'explorer' => 'https://nile.tronscan.org/'
        ]
    ];

    /**
     * @var array<string>
     */
    public array $node;

    /**
     * @var Tron
     */
    public Tron $tron;

    /**
     * @var bool
     */
    public bool $connectionFailed = false;

    /**
     * @var string
     */
    public string $connectionError = '';

    /**
     * @var Provider|null
     */
    private static ?Provider $instance;

    /**
     * @param array<string,mixed> $network
     */
    public function __construct(array $network)
    {
        $this->update($network);
    }

    /**
     * @return Provider
     */
    public static function instance(): Provider
    {
        if (null === self::$instance) {
            throw new \RuntimeException(ErrorType::PROVIDER_IS_NOT_INITIALIZED->value);
        }
        return self::$instance;
    }

    /**
     * @param array<string,mixed> $network
     * @return void
     */
    public static function initialize(array $network): void
    {
        if (null !== self::$instance) {
            throw new \RuntimeException(ErrorType::PROVIDER_IS_ALREADY_INITIALIZED->value);
        }
        self::$instance = new self($network);
    }

    /**
     * @param array<string,mixed> $network
     * @return void
     */
    public function update(array $network): void
    {
        self::$instance = $this;
        $this->network = new NetworkConfig($network);
        $this->node = $this->nodes[$this->network->isTestnet() ? 'testnet' : 'mainnet'];
        $this->node['host'] = $this->network->getRpcUrl() ?? $this->node['host'];
        $this->node['event'] = $this->network->getWsUrl() ?? $this->node['event'];
        try {
            $this->tron = new Tron(
                new HttpProvider($this->node['host']),
                new HttpProvider($this->node['host']),
                new HttpProvider($this->node['event'])
            );
        } catch (TronException $e) {
            $this->connectionFailed = true;
            $this->connectionError = $e->getMessage();
        }
    }

    /**
     * @return bool
     */
    public function isTestnet(): bool
    {
        return $this->network->isTestnet();
    }

    /**
     * @param string|null $url
     * @return bool
     */
    public function checkRpcConnection(?string $url = null): bool
    {
        try {
            if ($this->connectionFailed) {
                return false;
            }

            return $this->tron->getManager()->fullNode()->isConnected();
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * @param string|null $url
     * @return bool
     */
    public function checkWsConnection(?string $url = null): bool
    {
        try {
            if ($this->connectionFailed) {
                return false;
            }

            return $this->tron->getManager()->eventServer()->isConnected();
        } catch (\Throwable $th) {
            return false;
        }
    }
}
