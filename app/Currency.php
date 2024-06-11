<?php
namespace App;
class Currency
{
    private int $id;
    private string $name;
    private string $tickerSymbol;
    private float $price;
    private float $quantity;

    public function __construct(
        int $id,
        string $name,
        string $tickerSymbol,
        float $price,
        float $quantity
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->tickerSymbol = $tickerSymbol;
        $this->price = $price;
        $this->quantity = $quantity;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function tickerSymbol(): string
    {
        return $this->tickerSymbol;
    }

    public function price(): float
    {
        return $this->price;
    }

    public function quantity(): float
    {
        return $this->quantity;
    }

    public function addQuantity(float $amount): void
    {
        $this->quantity += $amount;
    }

}