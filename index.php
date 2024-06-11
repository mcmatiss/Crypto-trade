<?php

require_once 'app/Account.php';
require_once 'app/Currency.php';
require 'vendor/autoload.php';

use App\Account;
use App\Currency;
use Dotenv\Dotenv;
use LucidFrame\Console\ConsoleTable;

function fetchServerData(string $option, string $idSymbol = null): stdClass
{
    if ($option === 'list')
    {
        $url = 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/listings/latest';

        $parameters = [
            'start' => '1',
            'limit' => '10',
            'convert' => 'USD'
        ];
    }

    if ($option === 'search')
    {
        $url = "https://pro-api.coinmarketcap.com/v2/cryptocurrency/quotes/latest";

        $parameters = [
            'symbol' => "$idSymbol"
        ];
    }

    if ($option === 'id')
    {
        $url = "https://pro-api.coinmarketcap.com/v2/cryptocurrency/quotes/latest";

        $parameters = [
            'id' => "$idSymbol"
        ];
    }

    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $apiKey = $_ENV['APIKEY'];

    $headers = [
        'Accepts: application/json',
        "X-CMC_PRO_API_KEY: $apiKey"
    ];

    $qs = http_build_query($parameters);
    $request = "{$url}?{$qs}";

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $request,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => 1
    ));

    $response = curl_exec($curl);
    $response = json_decode($response);
    curl_close($curl);

    return $response;
}

$mainMenu = "\n1. Buy\n" .
    "2. Sell\n" .
    "3. Search\n" .
    "4. Add funds to the wallet\n" .
    "5. View transaction history\n" .
    "6. List top crypto currencies\n" .
    "7. Exit\n"
;

$SearchTableHeaders = [
    '#',
    'Id',
    'Name',
    'Symbol',
    'Price',
    '1h %',
    '24h %',
    '7d %',
    'Market Cap',
    'Volume(24h)',
    'Circulating Supply'
];

$tableHeaders = [
    '#',
    'Name',
    'Symbol',
    'Price',
    '1h %',
    '24h %',
    '7d %',
    'Market Cap',
    'Volume(24h)',
    'Circulating Supply'
];

$PortfolioTableHeaders = [
    '#',
    'Id',
    'Name',
    'Symbol',
    'Price',
    'Quantity',
    'Value',
    'Market Cap',
    'Volume(24h)',
    'Circulating Supply'
];

$account = new Account;

while (true)
{
    echo "Your Portfolio\n";
    $portfolio = new ConsoleTable();
    $portfolio
            ->setHeaders($PortfolioTableHeaders)
            ->setPadding(2)
    ;
    foreach ($account->portfolio() as $index => $currency)
    {
        $portfolioCurrencies = fetchServerData('id', $id=$currency->id());
        $value = $currency->quantity() * ($portfolioCurrencies
                ->data
                ->$id
                ->quote
                ->USD
                ->price)
        ;
        $portfolio->addRow([
            $index+1,
            $currency->id(),
            $currency->name(),
            $currency->tickerSymbol(),
            '$' . number_format($currency->price(), 2),
            $currency->quantity(),
            '$' . number_format($value, 2),
            '$' . number_format($portfolioCurrencies
                ->data
                ->$id
                ->quote
                ->USD
                ->market_cap, 2),
            '$' . number_format($portfolioCurrencies
                ->data
                ->$id
                ->quote
                ->USD
                ->volume_24h , 2),
            '$' . number_format($portfolioCurrencies
                ->data
                ->$id
                ->circulating_supply, 2)
        ]);
    }
    $portfolio->display();

    echo "Your Funds: $" . $account->funds() . "\n";
    echo $mainMenu;

    $menuSelection = (int) readline("Enter menu selection number: ");
    switch ($menuSelection)
    {
        case 1:
            $ticker = strtoupper(readline("Enter ticker symbol: "));
            $currencies = fetchServerData('search', $ticker);
            $currencies = $currencies->data->$ticker;
            $searchedCurrency = new ConsoleTable;
            $searchedCurrency
                ->setHeaders($SearchTableHeaders)
                ->setPadding(2)
            ;
            foreach ($currencies as $index => $currency)
            {
                if ($currency->quote->USD->price < 1)
                {
                    $price = number_format($currency->quote->USD->price,5);
                } else {
                    $price = number_format($currency->quote->USD->price,2);
                }
                $searchedCurrency
                    ->addRow([
                        $index+1,
                        $currency->id,
                        $currency->name,
                        $currency->symbol,
                        '$'.$price,
                        number_format($currency->quote->USD->percent_change_1h,2),
                        number_format($currency->quote->USD->percent_change_24h,2),
                        number_format($currency->quote->USD->percent_change_7d,2),
                        '$'.number_format($currency->quote->USD->market_cap, 2),
                        '$'.number_format($currency->quote->USD->volume_24h, 2),
                        '$'.number_format($currency->circulating_supply, 2)
                    ])
                ;
            }
            $searchedCurrency->display();
            $selectedCurrency = (int) readline("Enter currency index: ");
            echo $currencies[$selectedCurrency-1]->name .
                " - price: " .
                $currencies[$selectedCurrency-1]->quote->USD->price .
                "\n"
            ;
            $purchaseAmount = (float) readline("Enter the amount you wish to buy: ");
            if ($account->removeFunds($purchaseAmount))
            {
                $account->addToPortfolio(
                    new Currency(
                        $currencies[$selectedCurrency-1]->id,
                        $currencies[$selectedCurrency-1]->name,
                        $currencies[$selectedCurrency-1]->symbol,
                        $currencies[$selectedCurrency-1]->quote->USD->price,
                        $purchaseAmount
                    )
                );
            }
            break;
        case 2:
            $sellIndex = (int) readline("Enter Currency Index: ");
            $sellAmount = (int) readline("Enter Sell Amount: ");
            $id = $account->portfolio()[$sellIndex-1]->id();
            $soldCurrency = fetchServerData('id', $id);
            $value = $soldCurrency
                    ->data
                    ->$id
                    ->quote
                    ->USD
                    ->price
            ;
            $account->addFunds($sellAmount*$value);
            $account->removeFromPortfolio($sellIndex-1, $sellAmount);
            break;
        case 3:
            $ticker = strtoupper(readline("Enter ticker symbol: "));
            $currencies = fetchServerData('search', $ticker);
            $currencies = $currencies->data->$ticker;
            $searchedCurrency = new ConsoleTable;
            $searchedCurrency
                ->setHeaders($SearchTableHeaders)
                ->setPadding(2)
            ;
            foreach ($currencies as $index => $currency)
            {
                if ($currency->quote->USD->price < 1)
                {
                    $price = number_format($currency->quote->USD->price,5);
                } else {
                    $price = number_format($currency->quote->USD->price,2);
                }
                $searchedCurrency
                    ->addRow([
                        $index+1,
                        $currency->id,
                        $currency->name,
                        $currency->symbol,
                        '$'.$price,
                        number_format($currency->quote->USD->percent_change_1h,2),
                        number_format($currency->quote->USD->percent_change_24h,2),
                        number_format($currency->quote->USD->percent_change_7d,2),
                        '$'.number_format($currency->quote->USD->market_cap, 2),
                        '$'.number_format($currency->quote->USD->volume_24h, 2),
                        '$'.number_format($currency->circulating_supply, 2)
                    ])
                ;
            }
            $searchedCurrency->display();
            readline("Press any key to continue...");
            break;
        case 4:
            $amountUSD = (float) readline("Enter the USD amount: ");
            $account->addFunds($amountUSD);
            break;
        case 5:
            foreach ($account->history() as $entry => $values)
            {
                echo $entry;
            }
            break;
        case 6:
            $topCurrencies = fetchServerData('list');
            $topCurrencies = $topCurrencies->data;
            $topCurrenciesTable = new ConsoleTable;
            $topCurrenciesTable
                ->setHeaders($tableHeaders)
                ->setPadding(2)
            ;
            foreach ($topCurrencies as $index => $currency)
            {
                if ($currency->quote->USD->price < 1)
                {
                    $price = number_format($currency->quote->USD->price,5);
                } else {
                    $price = number_format($currency->quote->USD->price,2);
                }
                $topCurrenciesTable
                    ->addRow([
                        $index+1,
                        $currency->name,
                        $currency->symbol,
                        '$'.$price,
                        number_format($currency->quote->USD->percent_change_1h,2),
                        number_format($currency->quote->USD->percent_change_24h,2),
                        number_format($currency->quote->USD->percent_change_7d,2),
                        '$'.number_format($currency->quote->USD->market_cap, 2),
                        '$'.number_format($currency->quote->USD->volume_24h, 2),
                        '$'.number_format($currency->circulating_supply, 2)
                    ])
                ;
            }
            $topCurrenciesTable->display();
            break;
        case 7:
            return false;
        default:
            readline("Invalid input. Press any key to continue...");
    }
}
