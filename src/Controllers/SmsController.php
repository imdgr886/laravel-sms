<?php

namespace Imdgr886\Sms\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Imdgr886\Sms\Facades\Sms;

class SmsController extends Controller
{
    public function __construct()
    {
        $middleware = config('modules.sms.middleware', []);
        if ($middleware) {
            $this->middleware($middleware);
        }
    }

    public function postSendVerify()
    {
        $validator = Validator::make(request()->all(), [
            'mobile' => 'required|phone:CN',
            'senses' => 'string'
        ]);

        $mobile = request()->get('mobile');
        $scenes = request()->get('scenes', '');

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json([
                'success' => false,
                'message' => $errors->first(),
                'errors' => $errors
            ]);
        }

        if (!Sms::checkInterval($mobile, $scenes)) {
            return response()->json([
                'success' => false,
                'message' => "发送验证码太频繁，请稍候再试。",
            ]);
        }

        if (Sms::sendVerify($mobile, $scenes)) {
            return response()->json([
                'success' => true,
                'sent_at' => time(),
                'message' => '发送成功'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => '发送失败'
            ]);
        }

    }
}
