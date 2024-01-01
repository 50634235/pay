<?php

declare(strict_types=1);

namespace Yansongda\Pay\Plugin\Wechat\Pay\Combine;

use Closure;
use Throwable;
use Yansongda\Pay\Contract\PluginInterface;
use Yansongda\Pay\Exception\ContainerException;
use Yansongda\Pay\Exception\Exception;
use Yansongda\Pay\Exception\InvalidConfigException;
use Yansongda\Pay\Exception\InvalidResponseException;
use Yansongda\Pay\Exception\ServiceNotFoundException;
use Yansongda\Pay\Logger;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Rocket;
use Yansongda\Supports\Collection;
use Yansongda\Supports\Config;
use Yansongda\Supports\Str;

use function Yansongda\Pay\get_wechat_config;
use function Yansongda\Pay\get_wechat_sign;

/**
 * @see https://pay.weixin.qq.com/docs/merchant/apis/combine-payment/orders/mini-transfer-payment.html
 * @see https://pay.weixin.qq.com/docs/partner/apis/combine-payment/orders/mini-transfer-payment.html
 */
class MiniInvokePlugin implements PluginInterface
{
    /**
     * @throws ContainerException
     * @throws InvalidConfigException
     * @throws InvalidResponseException
     * @throws ServiceNotFoundException
     * @throws Throwable                生成随机串失败
     */
    public function assembly(Rocket $rocket, Closure $next): Rocket
    {
        /* @var Rocket $rocket */
        $rocket = $next($rocket);

        Logger::debug('[Wechat][Pay][Combine][MiniInvokePlugin] 插件开始装载', ['rocket' => $rocket]);

        $destination = $rocket->getDestination();
        $prepayId = $destination?->get('prepay_id');

        if (is_null($prepayId)) {
            Logger::error('[Wechat][Pay][Combine][MiniInvokePlugin] 预下单失败：响应缺少 `prepay_id` 参数，请自行检查参数是否符合微信要求', $destination?->all() ?? null);

            throw new InvalidResponseException(Exception::RESPONSE_MISSING_NECESSARY_PARAMS, $destination?->get('message') ?? '预下单失败：响应缺少 `prepay_id` 参数，请自行检查参数是否符合微信要求', $destination?->all() ?? null);
        }

        $params = $rocket->getParams();
        $config = get_wechat_config($params);
        $payload = $rocket->getPayload();

        $rocket->setDestination($this->getInvokeConfig($payload, $config, $prepayId));

        Logger::info('[Wechat][Pay][Combine][MiniInvokePlugin] 插件装载完毕', ['rocket' => $rocket]);

        return $rocket;
    }

    /**
     * @throws InvalidConfigException
     * @throws Throwable              生成随机串失败
     */
    protected function getInvokeConfig(?Collection $payload, array $config, string $prepayId): Config
    {
        $invokeConfig = new Config([
            'appId' => $this->getAppId($payload, $config),
            'timeStamp' => time().'',
            'nonceStr' => Str::random(32),
            'package' => 'prepay_id='.$prepayId,
            'signType' => 'RSA',
        ]);

        $invokeConfig->set('paySign', $this->getSign($invokeConfig, $config));

        return $invokeConfig;
    }

    /**
     * @throws InvalidConfigException
     */
    protected function getSign(Collection $invokeConfig, array $config): string
    {
        $contents = $invokeConfig->get('appId', '')."\n".
            $invokeConfig->get('timeStamp', '')."\n".
            $invokeConfig->get('nonceStr', '')."\n".
            $invokeConfig->get('package', '')."\n";

        return get_wechat_sign($config, $contents);
    }

    protected function getAppId(?Collection $payload, array $config): string
    {
        if (Pay::MODE_SERVICE === ($config['mode'] ?? Pay::MODE_NORMAL)) {
            return $payload?->get('_invoke_appid') ?? $config['sub_mini_app_id'] ?? '';
        }

        return $payload?->get('_invoke_appid') ?? $config['mini_app_id'] ?? '';
    }
}