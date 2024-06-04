<?php

declare(strict_types=1);

namespace MultipleChain\Tron;

class TransactionData
{
    /**
     * @var string
     */
    private string $function;

    /**
     * @var array<mixed>
     */
    private array $parameters;

    /**
     * @var int
     */
    private int $feeLimit;

    /**
     * @var string
     */
    private string $from;

    /**
     * @param string $function
     * @return TransactionData
     */
    public function setFunction(string $function): TransactionData
    {
        $this->function = $function;
        return $this;
    }

    /**
     * @param array<mixed> $parameters
     * @return TransactionData
     */
    public function setParameters(array $parameters): TransactionData
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @param int $feeLimit
     * @return TransactionData
     */
    public function setFeeLimit(int $feeLimit): TransactionData
    {
        $this->feeLimit = $feeLimit;
        return $this;
    }

    /**
     * @param string $from
     * @return TransactionData
     */
    public function setFrom(string $from): TransactionData
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @return string
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * @return array<mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return int
     */
    public function getFeeLimit(): int
    {
        return $this->feeLimit;
    }

    /**
     * @return string
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return [
            'function' => $this->getFunction(),
            'parameters' => $this->getParameters(),
            'feeLimit' => $this->getFeeLimit(),
            'from' => $this->getFrom(),
        ];
    }
}
