<?php

namespace App\Services;

use App\Models\SubscribeLog;
use App\Models\LoginLog;
use GeoIp2\Database\Reader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogService
{
    protected static ?Reader $cityReader = null;
    protected static ?Reader $asnReader  = null;

    /**
     * 写订阅日志（纯日志版）
     */
    public function writeSubscribeLog(Request $request, $user, string $flag, string $status = 'success'): void
    {
        try {
            $ip = $request->getClientIp();
            if (!$ip || !$user) return;

            $userObj = is_array($user) ? (object)$user : $user;

            $ua     = $request->userAgent() ?? '';
            $uaHash = $ua ? md5($ua) : null;

            $planId = $userObj->plan_id ?? null;
            $planName = $planId
                ? DB::table('v2_plan')->where('id', $planId)->value('name')
                : null;

            $geo  = $this->resolveGeo($ip);
            $host = $request->header('X-Forwarded-Host') ?? $request->getHost();

            SubscribeLog::create([
                'user_id'    => $userObj->id ?? null,
                'email'      => $userObj->email ?? null,
                'plan_id'    => $planId,
                'expired_at' => $userObj->expired_at ?? null,
                'plan_name'  => $planName,

                // UA解析字段全部留空
                'client_type'    => $flag,
                'client_name'    => null,
                'platform'       => null,
                'client_version' => null,

                'ip'   => $ip,
                'host' => $host,

                'country' => $geo['country'],
                'region'  => $geo['region'],
                'city'    => $geo['city'],
                'asn'     => $geo['asn'],
                'isp'     => $geo['isp'],

                'ua'      => $ua,
                'ua_hash' => $uaHash,
                'status'  => $status,
            ]);
        } catch (\Throwable $e) {
            Log::error('[SubscribeLog] write failed', [
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 写登录日志（纯日志）
     */
    public function writeLoginLog(Request $request, $user, string $status = 'success'): void
    {
        try {
            $ip = $request->getClientIp();
            if (!$ip) return;

            $userObj = is_array($user) ? (object)$user : $user;

            $ua   = $request->userAgent() ?? '';
            $host = $request->header('X-Forwarded-Host') ?? $request->getHost();
            $geo  = $this->resolveGeo($ip);

            LoginLog::create([
                'user_id' => $userObj->id ?? null,
                'email'   => $userObj->email ?? null,
                'ip'      => $ip,
                'host'    => $host,
                'country' => $geo['country'],
                'region'  => $geo['region'],
                'city'    => $geo['city'],
                'asn'     => $geo['asn'],
                'isp'     => $geo['isp'],
                'ua'      => $ua,
                'status'  => $status,
            ]);
        } catch (\Throwable $e) {
            Log::error('[LoginLog] write failed');
        }
    }

    private function resolveGeo(string $ip): array
    {
        $result = [
            'country' => null,
            'region'  => null,
            'city'    => null,
            'asn'     => null,
            'isp'     => null,
        ];

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return $result;
        }

        try {
            if (!self::$cityReader) {
                self::$cityReader = new Reader(storage_path('geoip/GeoLite2-City.mmdb'));
            }
            $city = self::$cityReader->city($ip);
            $result['country'] = $city->country->names['zh-CN'] ?? null;
            $result['region']  = $city->mostSpecificSubdivision->names['zh-CN'] ?? null;
            $result['city']    = $city->city->names['zh-CN'] ?? null;
        } catch (\Throwable $e) {}

        try {
            if (!self::$asnReader) {
                self::$asnReader = new Reader(storage_path('geoip/GeoLite2-ASN.mmdb'));
            }
            $asn = self::$asnReader->asn($ip);
            $result['asn'] = $asn->autonomousSystemNumber ?? null;
            $result['isp'] = $asn->autonomousSystemOrganization ?? null;
        } catch (\Throwable $e) {}

        return $result;
    }
}
