<?php

namespace Imdgr886\Sms;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Imdgr886\Sms\Events\SmsSendEvent;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

class Sms
{
    protected $state = null;

    /**
     * @param string $mobile
     * @return array
     */
    public function getState(string $mobile)
    {
        if ($this->state === null) {
            $this->state = Cache::get($this->generateCacheKey($mobile), []);
        }
        return $this->state;
    }

    /**
     * 更新发送状态
     * @param string $mobile
     * @param array $state
     * @return void
     */
    public function updateState(string $mobile, array $state)
    {
        $this->state = $state;
        Cache::put($this->generateCacheKey($mobile), $state, Carbon::now()->addMinutes(10));
    }

    /**
     * 发送短信
     * @param string       $scenes 场景，不同场景的可用渠道和模板可能不一样
     * @param array|string $mobile 手机号
     * @param array        $data  模板数据，用于替换
     * @return bool
     */
    public function send($mobile, array $data, string $scenes)
    {
        $gateways = $this->choiceGateways($scenes, $mobile);
        $success = false;
        $result = null;

        try {
            $result = app()->get('easysms')->send($mobile, [
                'content'  => function($gateway) use ($scenes) {
                    return Arr::get($this->template($scenes, $gateway->getName()), 'content');
                },
                'template' => function($gateway) use ($scenes) {
                    return Arr::get($this->template($scenes, $gateway->getName()), 'template_id');
                },
                'data' => $data
            ], $gateways);
            // 发送成功
            $success = true;
        } catch (Exception $e) {

        } finally {
            // 触发事件
            event(new SmsSendEvent($scenes, $mobile, $result));
            if ($result && !is_array($mobile)) {
                $state = $this->getState($mobile);
                $usedGateways = Arr::get($state, 'used_gateways', []);
                $state['used_gateways'] =array_unique(array_merge(array_keys($result), $usedGateways));
                $this->updateState($mobile, $state);
            }
        }
        return $success;
    }

    /**
     * 发送验证码短信
     * @param $mobile string
     * @param $scenes string
     * @return bool
     */
    public function sendVerify(string $mobile, $scenes = '')
    {
        $state = $this->getState($mobile);
        if (!empty($state) && $state['deadline'][$scenes] >= time() + 60) {
            $code = Arr::get($state, "code.{$scenes}");
        } else {
            $code = $this->generateVerifyCode();
        }
        Arr::set($state, "code.{$scenes}", $code);
        Arr::set($state, "deadline.{$scenes}", time() + 300);
        Arr::set($state, "lastsent.{$scenes}", time());
        $this->updateState($mobile, $state);

        return $this->send($mobile, ['code' => $code, 'exp' => 5], $scenes);
    }

    /**
     * 选择发送网关，同一个场景下尽量用没用过的网关
     * @param $scenes string 发送场景
     * @param $mobile array|string 手机号
     * @param $filterUsed bool 过滤已使用过的网关
     * @return array
     */
    protected function choiceGateways(string $scenes, $mobile, bool $filterUsed = true)
    {

        $gateways = config('modules.sms.default.gateways.' . $scenes);
        if (!$gateways) {
            $gateways = config('modules.sms.default.gateways.default');
        }

        // 如果是群发，不能进行筛选
        if (is_array($mobile) && count($mobile) > 1) {
            return $gateways;
        }

        if ($filterUsed) {
            // 发送失败的网关，再次发送最好不用
            $usedGateways = Arr::get($this->getState($mobile), 'used_gateways');

            // 还有没试过的网关， 才需要排除
            if($usedGateways && count($usedGateways) < count($gateways)){
                $gateways = array_diff($gateways, $usedGateways);
            }
        }
        return $gateways;
    }

    /**
     * @param string $scenes
     * @return mixed
     * @throws Exception
     */
    protected function template(string $scenes, string $gateway)
    {
        $templates = config('modules.sms.templates', []);
        if (!empty($templates[$scenes]) && !empty($templates[$scenes][$gateway])) {
            return $templates[$scenes][$gateway];
        } elseif (!empty($templates['default']) && !empty($templates['default'][$gateway])) {
            return $templates['default'][$gateway];
        } else {
            throw new Exception('No validate sms template can be use.');
        }
    }

    protected function generateCacheKey(string $mobile)
    {
        return 'imdgr886.sms.' . $mobile;
    }

    /**
     * 生成随机验证码
     * @return string
     */
    protected function generateVerifyCode()
    {
        $len = config('modules.sms.code.length', 6);
        $characters = '0123456789';
        $charLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $len; ++$i) {
            $randomString .= $characters[mt_rand(0, $charLength - 1)];
        }

        return $randomString;
    }

    public function checkInterval(string $mobile, $scenes)
    {
        $state = $this->getState($mobile);
        $lastSent = Arr::get($state, "lastsent.{$scenes}");
        $interval = config('modules.sms.code.interval', 60);
        if (!$state || !$lastSent || time() - $lastSent > $interval) {
            return true;
        } else {
            return false;
        }
    }
}
