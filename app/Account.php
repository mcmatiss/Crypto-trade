<?php
namespace App;
use JsonSerializable;

class Account implements JsonSerializable
{
    private float $funds = 1000;
    private array $portfolio = [];
    private array $history = [];

    public function __construct()
    {

    }

    public function funds(): float
    {
        return $this->funds;

    }

    public function addFunds(float $amount): void
    {
        if ($amount > 0) {
            $this->funds += $amount;
        }
    }

    public function removeFunds(float $amount): bool
    {
        if ($amount <= $this->funds) {
            $this->funds -= $amount;
            return true;
        }
        return false;
    }

    public function portfolio(): array
    {
        return $this->portfolio;
    }

    public function addToPortfolio(Currency $currency): void
    {
        $this->portfolio[] = $currency;

        $this->recordTransaction("bought", $currency);
    }

    public function removeFromPortfolio(int $index, float $amount): void
    {
        if ($this->portfolio[$index]->quantity() === $amount)
        {
            $this->recordTransaction("sold", $this->portfolio[$index]);

            array_splice($this->portfolio, $index, 1);
        }

        if ($this->portfolio[$index]->quantity() > $amount)
        {
            $this->recordTransaction("sold", $this->portfolio[$index]);

            $this->portfolio[$index]->addQuantity(-$amount);
        }
    }

    public function history(): array
    {
        return $this->history;
    }

    private function recordTransaction(string $message, Currency $currency = null): void
    {
        $this->history[$message] = $currency;
    }

    public function jsonSerialize()
    {
        // TODO: Implement jsonSerialize() method.
    }
}