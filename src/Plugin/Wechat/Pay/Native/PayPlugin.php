<?php

declare(strict_types=1);

namespace Yansongda\Pay\Plugin\Wechat\Pay\Native;

use Closure;
use Yansongda\Pay\Contract\PluginInterface;
use Yansongda\Pay\Exception\ContainerException;
use Yansongda\Pay\Exception\Exception;
use Yansongda\Pay\Exception\InvalidParamsException;
use Yansongda\Pay\Exception\ServiceNotFoundException;
use Yansongda\Pay\Logger;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Rocket;
use Yansongda\Supports\Collection;

use function Yansongda\Pay\get_wechat_config;
use function Yansongda\Pay\get_wechat_type_key;

/**
 * @see https://pay.weixin.qq.com/docs/merchant/apis/native-payment/direct-jsons/native-prepay.html
 * @see https://pay.weixin.qq.com/docs/partner/apis/partner-native-payment/partner-jsons/partner-native-prepay.html
 */
class PayPlugin implements PluginInterface
{
    /**
     * @throws ContainerException
     * @throws InvalidParamsException
     * @throws ServiceNotFoundException
     */
    public function assembly(Rocket $rocket, Closure $next): Rocket
    {
        Logger::debug('[Wechat][Pay][Native][PayPlugin] 插件开始装载', ['rocket' => $rocket]);

        $payload = $rocket->getPayload();
        $params = $rocket->getParams();
        $config = get_wechat_config($params);

        if (is_null($payload)) {
            throw new InvalidParamsException(Exception::PARAMS_NECESSARY_PARAMS_MISSING, '参数异常: Native 下单，参数为空');
        }

        if (Pay::MODE_SERVICE === ($config['mode'] ?? Pay::MODE_NORMAL)) {
            $data = $this->service($payload, $params, $config);
        }

        $rocket->mergePayload(array_merge(
            [
                '_method' => 'POST',
                '_url' => 'v3/pay/transactions/native',
                '_service_url' => 'v3/pay/partner/transactions/native',
                'notify_url' => $payload->get('notify_url', $config['notify_url'] ?? ''),
            ],
            $data ?? $this->normal($params, $config)
        ));

        Logger::info('[Wechat][Pay][Native][PayPlugin] 插件装载完毕', ['rocket' => $rocket]);

        return $next($rocket);
    }

    protected function normal(array $params, array $config): array
    {
        return [
            'appid' => $config[get_wechat_type_key($params)] ?? '',
            'mchid' => $config['mch_id'] ?? '',
        ];
    }

    protected function service(Collection $payload, array $params, array $config): array
    {
        $configKey = get_wechat_type_key($params);

        return [
            'sp_appid' => $config[$configKey] ?? '',
            'sp_mchid' => $config['mch_id'] ?? '',
            'sub_mchid' => $payload->get('sub_mchid', $config['sub_mch_id'] ?? ''),
        ];
    }
}
