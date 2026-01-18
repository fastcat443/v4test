<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscribeLog;
use App\Models\LoginLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    /**
     * 订阅访问日志
     */
    public function getSubscribeImportLogs(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $pageSize = 20;

        $logs = SubscribeLog::query()
            ->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $page);

        return response()->json([
            'page'    => $logs->currentPage(),
            'columns' => SubscribeLog::ADMIN_COLUMNS,
            'rows'    => $this->filterUserId($logs->items()),
        ]);
    }

    /**
     * 用户登录日志
     */
    public function getUserLoginLogs(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $pageSize = 20;

        $logs = LoginLog::query()
            ->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $page);

        return response()->json([
            'page'    => $logs->currentPage(),
            'columns' => LoginLog::ADMIN_COLUMNS,
            'rows'    => $this->filterUserId($logs->items()),
        ]);
    }

    /**
     * 移除 user_id（后台不展示）
     */
    private function filterUserId(array $rows): array
    {
        return array_map(function ($row) {
            unset($row->user_id);
            return $row;
        }, $rows);
    }
}
