<?php

declare(strict_types=1);

namespace Yansongda\Pay\Plugin\Wechat\Pay\Combine;

use Closure;
use Yansongda\Pay\Contract\PluginInterface;
use Yansongda\Pay\Exception\Exception;
use Yansongda\Pay\Exception\InvalidParamsException;
use Yansongda\Pay\Logger;
use Yansongda\Pay\Rocket;

/**
 * @see https://pay.weixin.qq.com/docs/merchant/apis/combine-payment/bill-download/get-fund-bill.html
 * @see https://pay.weixin.qq.com/docs/partner/apis/combine-payment/bill-download/get-fund-bill.html
 */
class GetFundBillPlugin implements PluginInterface
{
    /**
     * @throws InvalidParamsException
     */
    public function assembly(Rocket $rocket, Closure $next): Rocket
    {
        Logger::debug('[Wechat][Pay][Combine][GetFundBillPlugin] 插件开始装载', ['rocket' => $rocket]);

        $payload = $rocket->getPayload();

        if (is_null($payload)) {
            throw new InvalidParamsException(Exception::PARAMS_NECESSARY_PARAMS_MISSING, '参数异常: 合单 申请资金账单，参数为空');
        }

        $query = $payload->query();

        $rocket->setPayload([
            '_method' => 'GET',
            '_url' => 'v3/bill/fundflowbill?'.$query,
            '_service_url' => 'v3/bill/fundflowbill?'.$query,
        ]);

        Logger::info('[Wechat][Pay][Combine][GetFundBillPlugin] 插件装载完毕', ['rocket' => $rocket]);

        return $next($rocket);
    }
}
