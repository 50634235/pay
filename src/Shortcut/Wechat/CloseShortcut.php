<?php

declare(strict_types=1);

namespace Yansongda\Pay\Shortcut\Wechat;

use Yansongda\Pay\Contract\ShortcutInterface;
use Yansongda\Pay\Exception\Exception;
use Yansongda\Pay\Exception\InvalidParamsException;
use Yansongda\Pay\Plugin\ParserPlugin;
use Yansongda\Pay\Plugin\Wechat\AddPayloadBodyPlugin;
use Yansongda\Pay\Plugin\Wechat\AddPayloadSignaturePlugin;
use Yansongda\Pay\Plugin\Wechat\AddRadarPlugin;
use Yansongda\Pay\Plugin\Wechat\Pay\App\ClosePlugin as AppClosePlugin;
use Yansongda\Pay\Plugin\Wechat\Pay\Combine\ClosePlugin as CombineClosePlugin;
use Yansongda\Pay\Plugin\Wechat\Pay\H5\ClosePlugin as H5ClosePlugin;
use Yansongda\Pay\Plugin\Wechat\Pay\Jsapi\ClosePlugin as JsapiClosePlugin;
use Yansongda\Pay\Plugin\Wechat\Pay\Mini\ClosePlugin as MiniClosePlugin;
use Yansongda\Pay\Plugin\Wechat\Pay\Native\ClosePlugin as NativeClosePlugin;
use Yansongda\Pay\Plugin\Wechat\ResponsePlugin;
use Yansongda\Pay\Plugin\Wechat\StartPlugin;
use Yansongda\Pay\Plugin\Wechat\VerifySignaturePlugin;
use Yansongda\Supports\Str;

class CloseShortcut implements ShortcutInterface
{
    /**
     * @throws InvalidParamsException
     */
    public function getPlugins(array $params): array
    {
        if (isset($params['combine_out_trade_no']) || isset($params['sub_orders'])) {
            return $this->combinePlugins();
        }

        $action = Str::camel($params['_action'] ?? 'default').'Plugins';

        if (method_exists($this, $action)) {
            return $this->{$action}();
        }

        throw new InvalidParamsException(Exception::PARAMS_SHORTCUT_ACTION_INVALID, "Close action [{$action}] not supported");
    }

    protected function defaultPlugins(): array
    {
        return $this->jsapiPlugins();
    }

    protected function appPlugins(): array
    {
        return [
            StartPlugin::class,
            AppClosePlugin::class,
            AddPayloadBodyPlugin::class,
            AddPayloadSignaturePlugin::class,
            AddRadarPlugin::class,
            ResponsePlugin::class,
            VerifySignaturePlugin::class,
            ParserPlugin::class,
        ];
    }

    protected function H5Plugins(): array
    {
        return [
            StartPlugin::class,
            H5ClosePlugin::class,
            AddPayloadBodyPlugin::class,
            AddPayloadSignaturePlugin::class,
            AddRadarPlugin::class,
            ResponsePlugin::class,
            VerifySignaturePlugin::class,
            ParserPlugin::class,
        ];
    }

    protected function jsapiPlugins(): array
    {
        return [
            StartPlugin::class,
            JsapiClosePlugin::class,
            AddPayloadBodyPlugin::class,
            AddPayloadSignaturePlugin::class,
            AddRadarPlugin::class,
            ResponsePlugin::class,
            VerifySignaturePlugin::class,
            ParserPlugin::class,
        ];
    }

    protected function miniPlugins(): array
    {
        return [
            StartPlugin::class,
            MiniClosePlugin::class,
            AddPayloadBodyPlugin::class,
            AddPayloadSignaturePlugin::class,
            AddRadarPlugin::class,
            ResponsePlugin::class,
            VerifySignaturePlugin::class,
            ParserPlugin::class,
        ];
    }

    protected function nativePlugins(): array
    {
        return [
            StartPlugin::class,
            NativeClosePlugin::class,
            AddPayloadBodyPlugin::class,
            AddPayloadSignaturePlugin::class,
            AddRadarPlugin::class,
            ResponsePlugin::class,
            VerifySignaturePlugin::class,
            ParserPlugin::class,
        ];
    }

    protected function combinePlugins(): array
    {
        return [
            StartPlugin::class,
            CombineClosePlugin::class,
            AddPayloadBodyPlugin::class,
            AddPayloadSignaturePlugin::class,
            AddRadarPlugin::class,
            ResponsePlugin::class,
            VerifySignaturePlugin::class,
            ParserPlugin::class,
        ];
    }
}
