/**
 * FuturX - Common formatters
 * Single source of truth: DB `symbols.price_decimals/qty_decimals` exposed via window.FuturXConfig
 */

(function () {
    'use strict';

    function getConfig() {
        return window.FuturXConfig || {};
    }

    function getPriceDecimals(symbol) {
        const cfg = getConfig();
        const map = cfg.priceDecimalsBySymbol || {};
        if (symbol && map[symbol] !== undefined) return Number(map[symbol]) || 0;
        return 2;
    }

    function getQtyDecimals(symbol) {
        const cfg = getConfig();
        const map = cfg.qtyDecimalsBySymbol || {};
        if (symbol && map[symbol] !== undefined) return Number(map[symbol]) || 0;
        return 3;
    }

    function toNumber(value) {
        const n = typeof value === 'number' ? value : parseFloat(String(value).replace(/,/g, ''));
        return Number.isFinite(n) ? n : null;
    }

    function formatFixedNumber(value, decimals) {
        const n = toNumber(value);
        if (n === null) return '--';
        return n.toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function formatPrice(value, symbol) {
        return formatFixedNumber(value, getPriceDecimals(symbol));
    }

    function formatQty(value, symbol) {
        return formatFixedNumber(value, getQtyDecimals(symbol));
    }

    window.FuturX = window.FuturX || {};
    window.FuturX.getPriceDecimals = getPriceDecimals;
    window.FuturX.getQtyDecimals = getQtyDecimals;
    window.FuturX.formatPrice = formatPrice;
    window.FuturX.formatQty = formatQty;
})();

