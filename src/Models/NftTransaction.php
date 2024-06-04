<?php

declare(strict_types=1);

namespace MultipleChain\Tron\Models;

use MultipleChain\Tron\Provider;
use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Models\NftTransactionInterface;

class NftTransaction extends ContractTransaction implements NftTransactionInterface
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
            $abi ? $abi : json_decode(file_get_contents(dirname(__DIR__, 2) . '/resources/TRC721.json') ?: '')
        );
    }

    /**
     * @return string
     */
    public function getReceiver(): string
    {
        return '0x';
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        return '0x';
    }

    /**
     * @return string
     */
    public function getNftId(): int|string
    {
        return 0;
    }

    /**
     * @param AssetDirection $direction
     * @param string $address
     * @param int|string $nftId
     * @return TransactionStatus
     */
    public function verifyTransfer(AssetDirection $direction, string $address, int|string $nftId): TransactionStatus
    {
        return TransactionStatus::PENDING;
    }
}
