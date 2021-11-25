<?php

namespace Imdgr886\Sms\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SmsSendEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $scenes;
    protected $mobile;
    protected $result;

    public function __construct(string $scenes, string $mobile, $result)
    {
        $this->scenes = $scenes;
        $this->mobile = $mobile;
        $this->result = $result;
    }
}
