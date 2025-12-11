<?php

namespace App\Protocols;

use Symfony\Component\Yaml\Yaml;

class ClashForAndroid
{
    public $flag = 'clashforandroid';

    public function __construct($user, $servers) {}

    public function handle()
    {
        $names = [
            "本客户端由于过时已终止支持",
            "登官网 my.nowlink66.com",
            "登录后使用文档处可下最新版本",
            "Win用ClashVerge或者FLClash",
            "Android用ClashMetaforAndroid或者FLClash",
            "Mac用ClashVerge或FLClash",
            "请今后务必保持使用最新版本客户端",
            "不要再等不能用了再更新",
            "当前客户端请直接卸载不再需要了"
        ];

        $proxies = array_map(function ($n) {
            return [
                'name' => $n,
                'type' => 'vmess',
                'server' => 'demo.com',
                'port' => '1',
                'uuid' => '00000000-0000-0000-0000-000000000000',
                'alterId' => '0',
                'cipher' => 'auto'
            ];
        }, $names);

        $proxyGroups = [
            [
                'name' => 'NowLink',
                'type' => 'select',
                'proxies' => array_merge($names, ['auto', 'fallback'])
            ],
            [
                'name' => 'auto',
                'type' => 'url-test',
                'proxies' => $names,
                'url' => 'http://www.gstatic.com/generate_204',
                'interval' => 86400
            ],
            [
                'name' => 'fallback',
                'type' => 'fallback',
                'proxies' => $names,
                'url' => 'http://www.gstatic.com/generate_204',
                'interval' => 7200
            ]
        ];

        $yaml = [
            'proxies' => $proxies,
            'proxy-groups' => $proxyGroups,
            'rules' => [
                'GEOIP,CN,DIRECT',
                'MATCH,DIRECT'
            ]
        ];

        return Yaml::dump($yaml, 4, 2);
    }
}
