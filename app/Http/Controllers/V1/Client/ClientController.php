<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Protocols\General;
use App\Protocols\Singbox\Singbox;
use App\Protocols\Singbox\SingboxOld;
use App\Services\ServerService;
use App\Services\UserService;
use App\Services\LogService;
use App\Utils\Helper;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function subscribe(Request $request)
    {
        $flag = strtolower($request->input('flag') ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $user = $request->user;

        $logService = null;
        try { $logService = new LogService(); } catch (\Throwable $e) {}

        // ⭐ 提前解析最终协议flag（success/fail共用）
        $finalClientFlag = 'general';
        try {
            if (strpos($flag, 'sing') !== false) {
                $finalClientFlag = 'singbox';
            } else {
                foreach (array_reverse(glob(app_path('Protocols') . '/*.php')) as $filePath) {
                    $className = 'App\\Protocols\\' . basename($filePath, '.php');
                    if (!class_exists($className)) @require_once $filePath;
                    if (!class_exists($className)) continue;

                    $ref = new \ReflectionClass($className);
                    $pflag = strtolower($ref->getDefaultProperties()['flag'] ?? '');
                    if ($pflag && strpos($flag, $pflag) !== false) {
                        $finalClientFlag = $pflag;
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {}

        $userService = new UserService();

        if (!$userService->isAvailable($user)) {
            $this->safeWriteSubscribeLog($logService, $request, $user, $finalClientFlag, 'fail');
            return;
        }

        $servers = (new ServerService())->getAvailableServers($user);

        if ($flag) {
            if (!strpos($flag, 'sing')) {
                $this->setSubscribeInfoToServers($servers, $user);

                foreach (array_reverse(glob(app_path('Protocols') . '/*.php')) as $file) {
                    $file = 'App\\Protocols\\' . basename($file, '.php');
                    $class = new $file($user, $servers);

                    if (strpos($flag, $class->flag) !== false) {
                        $this->safeWriteSubscribeLog($logService, $request, $user, $class->flag, 'success');
                        return $class->handle();
                    }
                }
            }

            if (strpos($flag, 'sing') !== false) {
                $class = new Singbox($user, $servers);
                $this->safeWriteSubscribeLog($logService, $request, $user, 'singbox', 'success');
                return $class->handle();
            }
        }

        $class = new General($user, $servers);
        $this->safeWriteSubscribeLog($logService, $request, $user, 'general', 'success');
        return $class->handle();
    }

    private function safeWriteSubscribeLog($logService, Request $request, $user, string $flag, string $status): void
    {
        if (!$logService) return;
        try { $logService->writeSubscribeLog($request, $user, $flag, $status); } catch (\Throwable $e) {}
    }

    private function setSubscribeInfoToServers(&$servers, $user)
    {
        if (!isset($servers[0])) return;
        if (!(int)config('v2board.show_info_to_server_enable', 0)) return;

        $useTraffic = $user['u'] + $user['d'];
        $remainingTraffic = Helper::trafficConvert($user['transfer_enable'] - $useTraffic);
        $expiredDate = $user['expired_at'] ? date('Y-m-d', $user['expired_at']) : '长期有效';

        array_unshift($servers, array_merge($servers[0], ['name'=>"套餐到期：{$expiredDate}"]));
        array_unshift($servers, array_merge($servers[0], ['name'=>"剩余流量：{$remainingTraffic}"]));
    }
}
