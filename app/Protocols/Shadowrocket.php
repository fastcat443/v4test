<?php

namespace App\Protocols;

use App\Utils\Helper;

class Shadowrocket
{
    public $flag = 'shadowrocket';
    private $servers;
    private $user;

    /**
     * 缓存解析后的 Shadowrocket 版本（避免重复解析 UA）
     */
    private ?int $shadowrocketVersion = null;

    /**
     * 版本阈值
     */
    private const MIN_VERSION_BASIC  = 2007; // 最低可用
    private const MIN_VERSION_ANYTLS = 3000; // 高级协议完整支持

    public function __construct($user, $servers)
    {
        $this->user = $user;
        $this->servers = $servers;
    }

    public function handle()
    {
        /**
         * ================================
         * ① 版本 < 2007
         * 只返回升级提示虚拟节点
         * ================================
         */
        if (!$this->isShadowrocketBasicSupported()) {
            return base64_encode(
                $this->buildUpgradeNoticeNode()
            );
        }

        $user = $this->user;

        $uri = '';
        //display remaining traffic and expire date
        $upload = round($user['u'] / (1024*1024*1024), 2);
        $download = round($user['d'] / (1024*1024*1024), 2);
        $totalTraffic = round($user['transfer_enable'] / (1024*1024*1024), 2);
        $expiredDate = date('Y-m-d', $user['expired_at']);
        $uri .= "STATUS=🚀↑:{$upload}GB,↓:{$download}GB,TOT:{$totalTraffic}GB💡Expires:{$expiredDate}\r\n";

        foreach ($this->servers as $server) {
            if ($server['type'] === 'vmess' || ($server['type'] === 'v2node' && $server['protocol'] === 'vmess')) {
                $uri .= self::buildVmess($user['uuid'], $server);
            } else {
                $uri .= Helper::buildUri($this->user['uuid'], $server);
            }
        }

        /**
         * ================================
         * ③ 版本 < 4000
         * 插入“部分协议不支持”的虚拟节点
         * ================================
         */
        if (!$this->isShadowrocketAdvancedSupported()) {
            $uri .= $this->buildPartialSupportNoticeNode();
        }


        return base64_encode($uri);
    }

    /**
     * ================================
     * Shadowrocket 版本解析（只解析一次）
     * ================================
     */
    private function getShadowrocketVersion(): ?int
    {
        if ($this->shadowrocketVersion !== null) {
            return $this->shadowrocketVersion;
        }

        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (preg_match('/Shadowrocket\/(\d+)/i', $ua, $m)) {
            return $this->shadowrocketVersion = intval($m[1]);
        }

        return $this->shadowrocketVersion = null;
    }

    /**
     * ================================
     * 基础可用判断
     * ================================
     */
    private function isShadowrocketBasicSupported(): bool
    {
        $v = $this->getShadowrocketVersion();
        return $v !== null && $v >= self::MIN_VERSION_BASIC;
    }

    /**
     * ================================
     * 高级协议支持判断
     * ================================
     */
    private function isShadowrocketAdvancedSupported(): bool
    {
        $v = $this->getShadowrocketVersion();
        return $v !== null && $v >= self::MIN_VERSION_ANYTLS;
    }

    /**
     * ==================================================
     * 虚拟节点：版本过低 → 升级提示
     * ==================================================
     */
    private function buildUpgradeNoticeNode(): string
    {
        $uri = '';
    
        $uri .= $this->buildVirtualNode(
            'Shadowrocket 版本过低',
            '/upgrade-1'
        );
    
        $uri .= $this->buildVirtualNode(
            '请升级至最新版后重新订阅',
            '/upgrade-2'
        );
    
        return $uri;
    }

    /**
     * ==================================================
     * 虚拟节点：部分协议不支持提示
     * ==================================================
     */
    private function buildPartialSupportNoticeNode(): string
    {
        $uri = '';
    
        // 第一行：说明原因
        $uri .= $this->buildVirtualNode(
            '当前 Shadowrocket 版本较低',
            '/partial-support-1'
        );
    
        // 第二行：给出结果 / 行动指引
        $uri .= $this->buildVirtualNode(
            '部分高级协议不可用，升级可解锁',
            '/partial-support-2'
        );
    
        return $uri;
    }

    /**
     * ==================================================
     * 通用虚拟 vmess 节点构造器
     * ==================================================
     */
    private function buildVirtualNode(string $remark, string $path): string
    {
        // 使用 127.0.0.1，保证不可连接
        $userinfo = base64_encode('auto:notice@127.0.0.1:443');

        $config = [
            'remark'        => $remark,
            'alterId'       => 0,
            'tls'           => 1,
            'allowInsecure' => 1,
            'obfs'          => 'websocket',
            'path'          => $path,
            'obfsParam'     => 'notice.only',
        ];

        $query = http_build_query($config, '', '&', PHP_QUERY_RFC3986);

        return "vmess://{$userinfo}?{$query}\r\n";
    }

    /**
     * ==================================================
     * 【原样保留】buildVmess（无任何逻辑修改）
     * ==================================================
     */
    public static function buildVmess($uuid, $server)
    {
        $userinfo = base64_encode('auto:' . $uuid . '@' . $server['host'] . ':' . $server['port']);
        $config = [
            'tfo'     => 1,
            'remark'  => $server['name'],
            'alterId' => 0
        ];

        if ($server['tls']) {
            $config['tls'] = 1;
            $tlsSettings = $server['tls_settings'] ?? ($server['tlsSettings'] ?? []);
            $config['allowInsecure'] = (int)($tlsSettings['allow_insecure'] ?? $tlsSettings['allowInsecure'] ?? 0);
            $config['peer'] = $tlsSettings['server_name'] ?? $tlsSettings['serverName'] ?? '';
        }

        if ($server['network'] === 'tcp') {
            $tcpSettings = $server['network_settings'] ?? ($server['networkSettings'] ?? []);
            if (!empty($tcpSettings['header']['type']))
                $config['obfs'] = $tcpSettings['header']['type'];
            if (!empty($tcpSettings['header']['request']['path'][0]))
                $config['path'] = $tcpSettings['header']['request']['path'][0];
            if (!empty($tcpSettings['header']['request']['headers']['Host'][0]))
                $config['obfsParam'] = $tcpSettings['header']['request']['headers']['Host'][0];
        }

        if ($server['network'] === 'ws') {
            $config['obfs'] = "websocket";
            $wsSettings = $server['network_settings'] ?? ($server['networkSettings'] ?? []);
            if (!empty($wsSettings['path']))
                $config['path'] = $wsSettings['path'];
            if (!empty($wsSettings['headers']['Host']))
                $config['obfsParam'] = $wsSettings['headers']['Host'];
        }

        if ($server['network'] === 'grpc') {
            $config['obfs'] = "grpc";
            $grpcSettings = $server['network_settings'] ?? ($server['networkSettings'] ?? []);
            if (!empty($grpcSettings['serviceName']))
                $config['path'] = $grpcSettings['serviceName'];
            $config['host'] = $config['peer'] ?? $server['host'];
        }

        $query = http_build_query($config, '', '&', PHP_QUERY_RFC3986);
        return "vmess://{$userinfo}?{$query}\r\n";
    }
}
