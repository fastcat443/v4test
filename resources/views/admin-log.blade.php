<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>日志管理</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family:-apple-system, BlinkMacSystemFont, sans-serif; background:#f5f6fa; margin:0; }
        .topbar { background:#2c3e50; padding:16px; color:#fff; font-size:20px; }
        .container { padding:20px; }
        .card { background:#fff; padding:20px; border-radius:10px; box-shadow:0 0 8px rgba(0,0,0,0.05); }
        table { width:100%; border-collapse:collapse; margin-top:16px; }
        th,td { padding:10px; border-bottom:1px solid #eee; font-size:14px; }
        th { background:#fafafa; }
        .tabs button {
            padding:10px 16px; margin-right:10px; cursor:pointer;
            border:none; border-radius:6px; background:#e6e6e6;
        }
        .tabs button.active { background:#3498db; color:#fff; }
        .pagination { margin-top:15px; }
        .pagination button { padding:6px 12px; margin-right:10px; }
        .search-box { margin-top:12px; }
        .search-box input {
            padding:8px 12px; width:220px;
            border:1px solid #ccc; border-radius:6px;
        }
    </style>
</head>
<body>

<div class="topbar">日志管理 - 后台</div>

<div class="container">
    <div class="card">

        <div class="tabs">
            <button id="btn-import" class="active" onclick="switchLogType('import')">订阅导入日志</button>
            <button id="btn-login" onclick="switchLogType('login')">用户登录日志</button>
        </div>

        <!-- 搜索框：支持全局匹配 -->
        <div class="search-box">
            <input type="text" id="keyword" placeholder="全局搜索..." oninput="renderTable()">
        </div>

        <table>
            <thead id="tableHead"></thead>
            <tbody id="tableBody"></tbody>
        </table>

        <!-- 分页 -->
        <div class="pagination">
            <button onclick="prevPage()">上一页</button>
            <span id="pageInfo"></span>
            <button onclick="nextPage()">下一页</button>
        </div>

    </div>
</div>

<script>
const securePath = "{{ $securePath }}";
const token = localStorage.getItem("authorization");

if (!token) {
    alert("未检测到后台登录，请先登录后台");
    location.href = "/" + securePath;
}

async function api(path) {
    const res = await fetch(`/${securePath}/logadmin/api/${path}`, {
        headers: { authorization: token }
    });

    const text = await res.text();
    if (!res.ok) throw new Error(text);

    return JSON.parse(text);
}

let logType = "import";
let rows = [];
let columns = [];
let page = 1;

// 加载数据
async function loadLogs() {
    const path = logType === "import"
        ? `subscribe-import-logs?page=${page}`
        : `user-login-logs?page=${page}`;

    const data = await api(path);

    rows = data.rows;
    columns = data.columns.filter(c => c !== "user_id");

    document.getElementById("pageInfo").innerText = `第 ${data.page} 页`;

    renderTable();
}

// 全局搜索：任意字段匹配
function renderTable() {
    const keyword = document.getElementById("keyword").value.trim().toLowerCase();

    const head = document.getElementById("tableHead");
    const body = document.getElementById("tableBody");

    head.innerHTML = "<tr>" + columns.map(c => `<th>${c}</th>`).join("") + "</tr>";
    body.innerHTML = "";

    rows
        .filter(row => {
            if (!keyword) return true;
            return Object.values(row).some(v =>
                String(v ?? "").toLowerCase().includes(keyword)
            );
        })
        .forEach(row => {
            body.innerHTML +=
                "<tr>" +
                columns.map(c => `<td>${row[c] ?? ""}</td>`).join("") +
                "</tr>";
        });
}

function prevPage() {
    if (page > 1) {
        page--;
        loadLogs();
    }
}

function nextPage() {
    page++;
    loadLogs();
}

function switchLogType(type) {
    logType = type;
    page = 1;

    document.getElementById("btn-import").classList.toggle("active", type === "import");
    document.getElementById("btn-login").classList.toggle("active", type === "login");

    loadLogs();
}

loadLogs();
</script>

</body>
</html>
