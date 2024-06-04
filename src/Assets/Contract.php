<?php

declare(strict_types=1);

namespace MultipleChain\Tron\Assets;

use MultipleChain\Tron\Provider;
use Web3\Contracts\Ethabi as EthAbi;
use MultipleChain\Tron\TransactionData;
use phpseclib\Math\BigInteger as BigNumber;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Assets\ContractInterface;
use Web3\Contracts\Types\{Address, Boolean, Bytes, DynamicBytes, Integer, Str, Uinteger};

class Contract implements ContractInterface
{
    /**
     * @var string
     */
    private string $address;

    /**
     * @var array<string,mixed>
     */
    private array $cachedMethods = [];

    /**
     * @var string
     */
    private string $hexAddress;

    /**
     * @var Provider
     */
    protected Provider $provider;

    /**
     * @var array<object>
     */
    public array $abi;

    /**
     * @var array<array<mixed>>
     */
    private array $abiArray;

    /**
     * @var EthAbi
     */
    private EthAbi $ethAbi;

    /**
     * @param string $address
     * @param Provider|null $provider
     * @param array<object>|null $abi
     */
    public function __construct(string $address, ?ProviderInterface $provider = null, ?array $abi = null)
    {
        $this->abi = $abi ?? [];
        $this->address = $address;
        $this->provider = $provider ?? Provider::instance();
        $this->abiArray = json_decode(json_encode($this->abi) ?: '', true);
        $this->hexAddress = $this->provider->tron->address2HexString($this->address);
        $this->ethAbi =  new EthAbi([
            'address' => new Address(),
            'bool' => new Boolean(),
            'bytes' => new Bytes(),
            'dynamicBytes' => new DynamicBytes(),
            'int' => new Integer(),
            'string' => new Str(),
            'uint' => new Uinteger(),
        ]);
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
        $onlyParams = [];
        $address = array_reduce($args, function ($carry, $item) use (&$onlyParams) {
            if (is_array($item) && isset($item['from'])) {
                $carry = $this->provider->tron->address2HexString($item['from']);
            } else {
                $onlyParams[] = $item;
            }
            return $carry;
        });
        $ownerAddress = is_null($address) ? '410000000000000000000000000000000000000000' : $address;

        $result = $this->provider->tron->getTransactionBuilder()->triggerConstantContract(
            $this->abiArray,
            $this->hexAddress,
            $method,
            $onlyParams,
            $ownerAddress
        );

        $result = is_array($result) ? array_values($result)[0] : $result;

        if (is_null($result)) {
            return null;
        }

        if ($result instanceof BigNumber) {
            return '0x' . $result->toHex();
        }

        return $this->cleanStr($result);
    }

    /**
     * @param string $hex
     * @return string
     */
    public function addressFromHex(string $hex): string
    {
        return $this->provider->tron->hexString2Address(str_replace('0x', '41', trim($hex)));
    }

    /**
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    public function callMethodWithCache(string $method, mixed ...$args): mixed
    {
        if (isset($this->cachedMethods[$method])) {
            return $this->cachedMethods[$method];
        }

        return $this->cachedMethods[$method] = $this->callMethod($method, ...$args);
    }

    /**
     * @param string $str
     * @return string
     */
    private function cleanStr(string $str): string
    {
        try {
            $res = preg_replace('/[^\w.-]/', '', trim($str));
            return is_string($res) ? $res : (string) $res;
        } catch (\Throwable $th) {
            return trim($str);
        }
    }

    /**
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    public function getMethodData(string $method, mixed ...$args): mixed
    {
        throw new \Exception('Method not implemented.');
    }

    /**
     * @param string $function
     * @param array<mixed> $parameters
     * @param string $from
     * @return int
     */
    public function getEstimateEnergy(string $function, array $parameters, string $from): int
    {
        try {
            $funcAbi = array_reduce($this->abi, function ($carry, $item) use ($function) {
                if (isset($item->name) && $item->name === $function) {
                    $carry = $item;
                }
                return $carry;
            });

            $parameter = substr($this->ethAbi->encodeParameters($funcAbi, $parameters), 2);
            $res = $this->provider->tron->getManager()->request('wallet/estimateenergy', [
                'parameter' => $parameter,
                'contract_address' => $this->hexAddress,
                'function_selector' => $this->generateFunction($function),
                'owner_address' => $this->provider->tron->address2HexString($from),
            ]);
            return $res['energy_required'] ?? 0;
        } catch (\Throwable $th) {
            return 0;
        }
    }

    /**
     * @param string $method
     * @return string
     */
    private function generateFunction(string $method): string
    {
        $matchedItem = array_reduce($this->abi, function ($carry, $item) use ($method) {
            if (isset($item->name) && $item->name === $method) {
                $carry = $item;
            }
            return $carry;
        });

        if (null !== $matchedItem) {
            $output = $matchedItem->name . '(';
            $inputs = $matchedItem->inputs ?? [];
            foreach ($inputs as $index => $input) {
                if ($index > 0) {
                    $output .= ',';
                }
                $output .= $input->type;
            }
            $output .= ')';
            return $output;
        } else {
            return 'No matching function found.';
        }
    }

    /**
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    public function generateParameters(string $method, mixed ...$args): mixed
    {
        $matchedItem = array_reduce($this->abi, function ($carry, $item) use ($method) {
            if (isset($item->name) && $item->name === $method) {
                $carry = $item;
            }
            return $carry;
        });

        if (null !== $matchedItem) {
            $inputs = $matchedItem->inputs ?? [];
            $parameters = [];
            foreach ($inputs as $index => $input) {
                $parameters[] = [
                    'type' => $input->type,
                    'value' => $args[$index] ?? null,
                ];
            }
            return $parameters;
        } else {
            return 'No matching function found.';
        }
    }

    /**
     * @param string $method
     * @param string $from
     * @param mixed ...$args
     * @return TransactionData
     */
    public function createTransactionData(string $method, string $from, mixed ...$args): TransactionData
    {
        //$function = $this->generateFunction($method);
        //$parameters = $this->generateParameters($method, ...$args);

        return (new TransactionData())
            ->setFunction($method)
            ->setParameters($args)
            ->setFeeLimit(100000000)
            ->setFrom($from);
    }

    /**
     * @param TransactionData $data
     * @return mixed
     */
    public function triggerContract(TransactionData $data): mixed
    {
        try {
            return $this->provider->tron->getTransactionBuilder()->triggerSmartContract(
                $this->abiArray,
                $this->hexAddress,
                $data->getFunction(),
                $data->getParameters(),
                $data->getFeeLimit(),
                $data->getFrom()
            );
        } catch (\Throwable $th) {
            throw new \RuntimeException($th->getMessage(), $th->getCode());
        }
    }
}
