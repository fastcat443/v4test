<?php

namespace App\Http\Controllers\V1\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class LogController extends Controller
{
    // 订阅导入日志（无搜索）
    public function getSubscribeImportLogs(Request $request)
    {
        $page = (int) ($request->get('page', 1));
        $pageSize = 20;

        $logs = DB::table('subscribe_logs')
            ->orderBy('id', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);

        return response()->json([
            'page' => $logs->currentPage(),
            'columns' => [
                'email', 'plan_name', 'ip', 'location', 'client_type', 'ua', 'created_at'
            ],
            'rows' => $this->filterUserId($logs->items())
        ]);
    }

    // 用户登录日志（无搜索）
    public function getUserLoginLogs(Request $request)
    {
        $page = (int) ($request->get('page', 1));
        $pageSize = 20;

        $logs = DB::table('login_logs')
            ->orderBy('id', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);

        return response()->json([
            'page' => $logs->currentPage(),
            'columns' => [
                 'email', 'ip', 'host', 'location', 'ua', 'created_at'
            ],
            'rows' => $this->filterUserId($logs->items())
        ]);
    }

    // 移除 user_id 字段（你说不要显示它）
    private function filterUserId($rows)
    {
        return array_map(function ($row) {
            unset($row->user_id);
            return $row;
        }, $rows);
    }
}
