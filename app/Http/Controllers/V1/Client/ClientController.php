<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Protocols\General;
use App\Protocols\Singbox\Singbox;
use App\Protocols\Singbox\SingboxOld;
use App\Protocols\ClashMeta;
use App\Services\ServerService;
use App\Services\UserService;
use App\Utils\Helper;
use Illuminate\Http\Request;
use GeoIp2\Database\Reader;

class ClientController extends Controller
{
    public function subscribe(Request $request)
    {
        $flag = $request->input('flag')
            ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $flag = strtolower($flag);
        $user = $request->user;
        // account not expired and is not banned.
        $userService = new UserService();
        if ($userService->isAvailable($user)) {
            $serverService = new ServerService();
            $servers = $serverService->getAvailableServers($user);
            
            // ⭐ 记录订阅日志
            $this->writeSubscribeLog($request, $user, $flag);
            
            
            if($flag) {
                if (!strpos($flag, 'sing')) {
                    $this->setSubscribeInfoToServers($servers, $user);
                    foreach (array_reverse(glob(app_path('Protocols') . '/*.php')) as $file) {
                        $file = 'App\\Protocols\\' . basename($file, '.php');
                        $class = new $file($user, $servers);
                        if (strpos($flag, $class->flag) !== false) {
                            return $class->handle();
                        }
                    }
                }
                if (strpos($flag, 'sing') !== false) {
                    $version = null;
                    if (preg_match('/sing-box\s+([0-9.]+)/i', $flag, $matches)) {
                        $version = $matches[1];
                    }
                    if (!is_null($version) && $version >= '1.12.0') {
                        $class = new Singbox($user, $servers);
                    } else {
                        $class = new SingboxOld($user, $servers);
                    }
                    return $class->handle();
                }
            }
            $class = new General($user, $servers);
            return $class->handle();
        }
    }

    private function setSubscribeInfoToServers(&$servers, $user)
    {
        if (!isset($servers[0])) return;
        if (!(int)config('v2board.show_info_to_server_enable', 0)) return;
        $useTraffic = $user['u'] + $user['d'];
        $totalTraffic = $user['transfer_enable'];
        $remainingTraffic = Helper::trafficConvert($totalTraffic - $useTraffic);
        $expiredDate = $user['expired_at'] ? date('Y-m-d', $user['expired_at']) : '长期有效';
        $userService = new UserService();
        $resetDay = $userService->getResetDay($user);
        array_unshift($servers, array_merge($servers[0], [
            'name' => "套餐到期：{$expiredDate}",
        ]));
        if ($resetDay) {
            array_unshift($servers, array_merge($servers[0], [
                'name' => "距离下次重置剩余：{$resetDay} 天",
            ]));
        }
        array_unshift($servers, array_merge($servers[0], [
            'name' => "剩余流量：{$remainingTraffic}",
        ]));
    }

    /**
     * ⭐ 完全恢复版本：订阅访问日志写入
     */
    private function writeSubscribeLog(Request $request, $user, $flag)
    {
        try {
            // 获取套餐名称
            $planName = \DB::table('v2_plan')
                ->where('id', $user->plan_id)
                ->value('name');
    
            $location = '未知';
            $ip = $request->ip();
    
            try {
                // 读取城市数据库
                $readerCity = new Reader(storage_path('/geoip/GeoLite2-City.mmdb'));
                $record = $readerCity->city($ip);
    
                $country = $record->country->names['zh-CN'] ?? '';
                $subdiv  = $record->mostSpecificSubdivision->names['zh-CN'] ?? '';
                $city    = $record->city->names['zh-CN'] ?? '';
    
                $location = trim("$country $subdiv $city");
    
                // 如果城市信息为空 → 降级使用 Country 库
                if (empty($location)) {
                    $readerCountry = new Reader(storage_path('/geoip/GeoLite2-Country.mmdb'));
                    $recordCountry = $readerCountry->country($ip);
                    $location = $recordCountry->country->names['zh-CN'] ?? '未知';
                }
    
            } catch (\Exception $e) {
                \Log::warning("GeoIP lookup failed for IP {$ip}: " . $e->getMessage());
            }
    
            // 插入订阅请求日志
            \DB::table('subscribe_logs')->insert([
                'user_id'     => $user->id,
                'email'       => $user->email,
                'plan_id'     => $user->plan_id,
                'plan_name'   => $planName,
                'client_type' => $this->detectClientType($flag),
                'ip'          => $ip,
                'location'    => $location,
                'ua'          => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at'  => now(),
                'updated_at'  => now()
            ]);
    
        } catch (\Throwable $e) {
            \Log::error('Subscribe log failed: ' . $e->getMessage());
        }
    }


    /**
     * ⭐ UA → 客户端类型识别（你的项目之前必定已经定义过）
     * 如果你需要我优化请告诉我
     */
    private function detectClientType($flag)
    {
        // 统一转小写，避免遗漏
        $flag = strtolower($flag);
    
        // Clash 系列
        if (strpos($flag, 'clash-for-android') !== false || strpos($flag, 'cfa') !== false) return 'clash-for-android';
        if (strpos($flag, 'clash verge') !== false || strpos($flag, 'verge') !== false) return 'clash-verge';
        if (strpos($flag, 'clash meta') !== false || strpos($flag, 'meta') !== false) return 'clash-meta';
        if (strpos($flag, 'clash') !== false) return 'clash';
    
        // Sing-box 系列
        if (strpos($flag, 'sing-box') !== false || strpos($flag, 'singbox') !== false || strpos($flag, 'sfa') !== false) return 'singbox';
    
        // Shadowrocket
        if (strpos($flag, 'shadowrocket') !== false) return 'shadowrocket';
    
        // Quantumult X
        if (strpos($flag, 'quantumult x') !== false || strpos($flag, 'qx') !== false) return 'quantumultx';
    
        // Quantumult
        if (strpos($flag, 'quantumult') !== false) return 'quantumult';
    
        // Loon (iOS)
        if (strpos($flag, 'loon') !== false) return 'loon';
    
        // Stash (iOS)
        if (strpos($flag, 'stash') !== false) return 'stash';
    
        // Surfboard (Android)
        if (strpos($flag, 'surfboard') !== false) return 'surfboard';
    
        // Shadowsocks 系列（ss / ssr / ss-local / libev 等）
        if (strpos($flag, 'shadowsocks') !== false) return 'shadowsocks';
        if (strpos($flag, 'ssr') !== false) return 'shadowsocks-r';
        if (strpos($flag, 'ss-local') !== false || strpos($flag, 'ss-local') !== false) return 'shadowsocks';
        if (strpos($flag, 'shadowsocks-libev') !== false) return 'shadowsocks';
    
        // V2Ray / XRay 系
        if (strpos($flag, 'v2rayn') !== false) return 'v2rayn';
        if (strpos($flag, 'xray') !== false || strpos($flag, 'v2ray') !== false) return 'v2ray';
    
        // Nekobox / Nekoray
        if (strpos($flag, 'nekobox') !== false) return 'nekobox';
        if (strpos($flag, 'nekoray') !== false) return 'nekoray';
    
        // Kitsunebi
        if (strpos($flag, 'kitsunebi') !== false) return 'kitsunebi';
    
        // SagerNet / AnXray
        if (strpos($flag, 'sagernet') !== false) return 'sagernet';
        if (strpos($flag, 'anxray') !== false) return 'anxray';
    
        // fallback
        return 'general';
    }

}    

