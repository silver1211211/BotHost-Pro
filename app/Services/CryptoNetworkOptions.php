<?php

namespace App\Services;

class CryptoNetworkOptions
{
    private static array $map = [
        'BTC'          => ['pay_currency' => 'BTC',  'network' => null,      'label' => 'Bitcoin (BTC)'],
        'ETH_ETHEREUM' => ['pay_currency' => 'ETH',  'network' => 'ERC20',   'label' => 'ETH Ethereum'],
        'ETH_BASE'     => ['pay_currency' => 'ETH',  'network' => 'Base',    'label' => 'ETH Base'],
        'USDT_ERC20'   => ['pay_currency' => 'USDT', 'network' => 'ERC20',   'label' => 'USDT ERC20'],
        'USDT_TRC20'   => ['pay_currency' => 'USDT', 'network' => 'TRC20',   'label' => 'USDT TRC20'],
        'USDT_BEP20'   => ['pay_currency' => 'USDT', 'network' => 'BEP20',   'label' => 'USDT BEP20'],
        'USDT_POLYGON' => ['pay_currency' => 'USDT', 'network' => 'Polygon', 'label' => 'USDT Polygon'],
        'USDT_TON'     => ['pay_currency' => 'USDT', 'network' => 'TON',     'label' => 'USDT TON'],
        'USDC_ERC20'   => ['pay_currency' => 'USDC', 'network' => 'ERC20',   'label' => 'USDC ERC20'],
        'BNB_BEP20'    => ['pay_currency' => 'BNB',  'network' => 'BEP20',   'label' => 'BNB BEP20'],
        'DOGE'         => ['pay_currency' => 'DOGE', 'network' => null,      'label' => 'Dogecoin (DOGE)'],
        'POL_POLYGON'  => ['pay_currency' => 'POL',  'network' => 'Polygon', 'label' => 'POL Polygon'],
        'LTC'          => ['pay_currency' => 'LTC',  'network' => null,      'label' => 'Litecoin (LTC)'],
        'SOL'          => ['pay_currency' => 'SOL',  'network' => null,      'label' => 'Solana (SOL)'],
        'TRX_TRC20'    => ['pay_currency' => 'TRX',  'network' => 'TRC20',   'label' => 'TRX TRC20'],
        'SHIB_BEP20'   => ['pay_currency' => 'SHIB', 'network' => 'BEP20',   'label' => 'SHIB BEP20'],
        'TON'          => ['pay_currency' => 'TON',  'network' => null,      'label' => 'Toncoin (TON)'],
        'XMR'          => ['pay_currency' => 'XMR',  'network' => null,      'label' => 'Monero (XMR)'],
        'DAI_POLYGON'  => ['pay_currency' => 'DAI',  'network' => 'Polygon', 'label' => 'DAI Polygon'],
        'BCH'          => ['pay_currency' => 'BCH',  'network' => null,      'label' => 'Bitcoin Cash (BCH)'],
        'NOT_TON'      => ['pay_currency' => 'NOT',  'network' => 'TON',     'label' => 'NOT TON'],
        'DOGS_TON'     => ['pay_currency' => 'DOGS', 'network' => 'TON',     'label' => 'DOGS TON'],
    ];

    /** Returns value => label pairs for dropdowns. */
    public static function options(): array
    {
        return array_map(fn (array $v) => $v['label'], self::$map);
    }

    /** Returns all valid keys for validation. */
    public static function values(): array
    {
        return array_keys(self::$map);
    }

    /**
     * Resolve a UI key into its OxaPay API fields.
     *
     * @return array{pay_currency: string, network: string|null, label: string}
     */
    public static function normalize(string $value): array
    {
        return self::$map[$value] ?? ['pay_currency' => $value, 'network' => null, 'label' => $value];
    }
}
