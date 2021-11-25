<?php

namespace Imdgr886\Sms;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Imdgr886\Sms\Controllers\SmsController;
use Overtrue\EasySms\EasySms;

class SmsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('easysms', function () {
            return new EasySms(config('modules.sms'));
        });
    }

    public function boot()
    {
        $this->defineRoutes();

        $this->extendValidator();

        if (!$this->app->runningInConsole()) {
            return;
        }

        // publish
        $this->publishes([__DIR__. '/../config/sms.php' => config_path('modules/sms.php')], 'sms');
    }

    protected function defineRoutes()
    {
        if (app()->routesAreCached()) {
            return;
        }

        Route::middleware(['api'])->prefix('api')->group(function () {
            Route::post('/sms/verify/send', SmsController::class.'@postSendVerify');
        });
    }

    protected function extendValidator()
    {
        Validator::extend('verify_code', function ($attribute, $value, $params, \Illuminate\Validation\Validator $validator) {
            if ($value == '') return false;
            $mobileField = Arr::get($params, 0, 'mobile');
            $scenes = Arr::get($params, 1, '');
            $mobile = Arr::get($validator->getData(), $mobileField, '');

            $state = \Imdgr886\Sms\Facades\Sms::getState($mobile);

            $valid = @$state && $state['deadline'][$scenes] >= time() && $state['code'][$scenes] == $value;
            if ($valid) {
                \Imdgr886\Sms\Facades\Sms::updateState($mobile, []);
            }
            return $valid;
        }, '验证码错误');
    }
}
