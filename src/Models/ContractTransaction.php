<?php

declare(strict_types=1);

namespace MultipleChain\Tron\Models;

use MultipleChain\Tron\Provider;
use MultipleChain\Tron\AbiDecoder;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Models\ContractTransactionInterface;

class ContractTransaction extends Transaction implements ContractTransactionInterface
{
    /**
     * @var array<object>
     */
    public array $abi;

    /**
     * @param string $id
     * @param Provider|null $provider
     * @param array<object>|null $abi
     */
    public function __construct(string $id, ?ProviderInterface $provider = null, ?array $abi = null)
    {
        $this->abi = $abi ?? [];
        parent::__construct($id, $provider);
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        $data = $this->getData();
        return $this->provider->tron->hexString2Address(
            $data['raw_data']['contract'][0]['parameter']['value']['contract_address'] ?? ''
        );
    }

    /**
     * @param object|null $data
     * @return object|null
     */
    public function decodeData(?object $data = null): ?object
    {
        if (is_null($data)) {
            $data = $this->getData();
            if (is_null($data)) {
                return null;
            }
        }

        $decoder = new AbiDecoder($this->abi);
        return $decoder->decodeInput(
            $data['raw_data']['contract'][0]['parameter']['value']['data'] ?? ''
        );
    }
}
