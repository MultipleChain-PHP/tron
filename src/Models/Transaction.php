<?php

declare(strict_types=1);

namespace MultipleChain\Tron\Models;

use MultipleChain\Utils\Number;
use MultipleChain\Tron\Provider;
use MultipleChain\Tron\Assets\NFT;
use MultipleChain\Enums\ErrorType;
use MultipleChain\Tron\Assets\Coin;
use MultipleChain\Enums\TransactionType;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Models\TransactionInterface;

class Transaction implements TransactionInterface
{
    /**
     * @var string
     */
    private string $id;

    /**
     * @var mixed
     */
    private mixed $data = null;

    /**
     * @var Provider
     */
    protected Provider $provider;

    /**
     * @param string $id
     * @param Provider|null $provider
     */
    public function __construct(string $id, ?ProviderInterface $provider = null)
    {
        $this->id = $id;
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getData(): mixed
    {
        if (isset($this->data['info'])) {
            return $this->data;
        }

        try {
            $this->data = $this->provider->tron->getTransaction($this->id);
            $result = $this->provider->tron->getTransactionInfo($this->id);
            $this->data['info'] = isset($result['id']) ? $result : null;
            return $this->data;
        } catch (\Throwable $th) {
            throw new \RuntimeException(ErrorType::RPC_REQUEST_ERROR->value);
        }
    }

    /**
     * @param int|null $ms
     * @return TransactionStatus
     */
    public function wait(?int $ms = 4000): TransactionStatus
    {
        try {
            $status = $this->getStatus();
            if (TransactionStatus::PENDING != $status) {
                return $status;
            }

            sleep($ms / 1000);

            return $this->wait($ms);
        } catch (\Throwable $th) {
            return TransactionStatus::FAILED;
        }
    }

    /**
     * @return TransactionType
     */
    public function getType(): TransactionType
    {
        $data = $this->getData();

        if (null === $data) {
            return TransactionType::GENERAL;
        }

        $selectors = [
            // TRC20
            'a9059cbb', // transfer(address,uint256)
            '095ea7b3', // approve(address,uint256)
            '23b872dd', // transferFrom(address,address,uint256)
            // TRC721
            '42842e0e', // safeTransferFrom(address,address,uint256)
            'b88d4fde', // safeTransferFrom(address,address,uint256,bytes)
            // TRC1155
            'f242432a', // safeTransferFrom(address,address,uint256,uint256,bytes)
            '2eb2c2d6', // safeBatchTransferFrom(address,address,uint256[],uint256[],bytes)
            '29535c7e' // setApprovalForAll(address,bool)
        ];

        if ('TriggerSmartContract' === $data['raw_data']['contract'][0]['type']) {
            $val = $data['raw_data']['contract'][0]['parameter']['value'];
            $selectorId = substr($val['data'], 0, 8);
            if (in_array($selectorId, $selectors)) {
                try {
                    $tryNft = new NFT($val['contract_address'] ?? '');
                    $tryNft->getOwner(1);
                    return TransactionType::NFT;
                } catch (\Throwable $th) {
                    return TransactionType::TOKEN;
                }
            }
            return TransactionType::CONTRACT;
        } elseif ('TransferContract' === $data['raw_data']['contract'][0]['type']) {
            return TransactionType::COIN;
        }

        return TransactionType::GENERAL;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        $explorerUrl = $this->provider->node['explorer'];
        $explorerUrl .= '/' === substr($explorerUrl, -1) ? '' : '/';
        $explorerUrl .= '#/transaction/' . $this->id;
        return $explorerUrl;
    }

    /**
     * @return string
     */
    public function getSigner(): string
    {
        $data = $this->getData();

        if (null === $data) {
            return '';
        }

        $addr = $data['raw_data']['contract'][0]['parameter']['value']['owner_address'];

        return $this->provider->tron->hexString2Address($addr);
    }

    /**
     * @return Number
     */
    public function getFee(): Number
    {
        $data = $this->getData();

        if (null === $data) {
            return new Number(0);
        }

        $decimals = (new Coin())->getDecimals();
        $fee = isset($data['info']['fee']) ? $data['info']['fee'] : 0;
        return new Number($this->provider->tron->fromTron($fee), $decimals);
    }

    /**
     * @return int
     */
    public function getBlockNumber(): int
    {
        $data = $this->getData();

        if (null === $data) {
            return 0;
        }

        return $data['info']['blockNumber'] ?? 0;
    }

    /**
     * @return int
     */
    public function getBlockTimestamp(): int
    {
        $data = $this->getData();

        if (null === $data) {
            return 0;
        }

        return (int) rtrim((string) $data['info']['blockTimeStamp'], '000');
    }

    /**
     * @return int
     */
    public function getBlockConfirmationCount(): int
    {
        $data = $this->getData();

        if (null === $data) {
            return 0;
        }

        $blockNumber = $this->getBlockNumber();
        $latestBlock = $this->provider->tron->getCurrentBlock();
        return $latestBlock['block_header']['raw_data']['number'] - $blockNumber;
    }

    /**
     * @return TransactionStatus
     */
    public function getStatus(): TransactionStatus
    {
        $data = $this->getData();

        if (null === $data) {
            return TransactionStatus::PENDING;
        } elseif (isset($data['ret'][0]) && isset($data['info'])) {
            if (isset($data['info']['blockNumber'])) {
                $result = $data['info']['result'] ?? '';
                if ('REVERT' === $data['ret'][0]['contractRet'] || 'FAILED' === $result) {
                    return TransactionStatus::FAILED;
                } else {
                    return TransactionStatus::CONFIRMED;
                }
            }
        }

        return TransactionStatus::PENDING;
    }
}
