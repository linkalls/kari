<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["user_id"] == 0) {
 header("Location: login.php");
 exit;
}

include 'db.php';

$shortUrl = $_GET['short_url'];

$sql = "SELECT u.original_url, u.short_url, u.created_at, COUNT(a.accessed_at) as access_count, GROUP_CONCAT(DISTINCT a.client_ip) as client_ip, GROUP_CONCAT(DISTINCT a.user_agent) as user_agents
 FROM short_urls u 
 LEFT JOIN url_accesses a ON u.id = a.short_url_id
 WHERE u.short_url = ? AND u.user_id = ? AND u.user_id != 0
 GROUP BY u.original_url, u.short_url, u.created_at";
$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $shortUrl, $_SESSION['user_id']);
$stmt->execute();

$result = $stmt->get_result();
$url = $result->fetch_assoc();
$stmt->close();

if (!$url) {
 header("Location: dashboard.php");
 exit;
}

$sql = "SELECT a.accessed_at, a.client_ip, a.user_agent
 FROM url_accesses a
 WHERE a.short_url_id = (SELECT u.id FROM short_urls u WHERE u.short_url = ? AND u.user_id = ? AND u.user_id != 0)
 ORDER BY a.accessed_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $shortUrl, $_SESSION['user_id']);
$stmt->execute();

$result = $stmt->get_result();
$accesses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
 <title>URL詳細</title>
 <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
 <!-- Chart.jsを読み込みます -->
 <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 p-4 sm:p-10">
 <div class="container mx-auto flex flex-col items-center justify-center min-h-screen">
    <h1 class="text-2xl sm:text-3xl font-bold mb-4 text-center mt-10">URL詳細</h1>
    <!-- アクセス解析のグラフを表示します。 -->
<canvas id="accessChart" class="mb-4" style="max-width: 100%; max-height: 500px; width: 100%; height: auto;"></canvas>
    <div class="w-full sm:w-96 bg-white rounded-lg shadow-lg p-6 mb-8 mx-auto">
      <p><strong>元URL:</strong> <?php echo $url['original_url']; ?></p>
      <p><strong>短縮URL:</strong> <?php echo $url['short_url']; ?></p>
      <p><strong>作成日:</strong> <?php echo $url['created_at']; ?></p>
      <p><strong>アクセス数:</strong> <?php echo $url['access_count']; ?></p>
    
    </div>
    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mx-auto block" onclick="redirectToDashboard()">
      ダッシュボードに戻る
    </button>
    <!-- アクセス履歴表示 -->
    <div class="w-full sm:w-96 bg-white rounded-lg shadow-lg p-6 mb-8 mx-auto">
      <table id="accessHistoryTable" class="w-full text-left table-auto">
        <thead>
          <tr>
            <th class="px-4 py-2">アクセス日時 <button class="sort-button">▽</button></th>
            <th class="px-4 py-2">IPアドレス</th>
            <th class="px-4 py-2">ブラウザ情報</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($accesses as $access): ?>
            <tr>
              <td class="border px-4 py-2"><?php echo $access['accessed_at']; ?></td>
              <td class="border px-4 py-2"><?php echo $access['client_ip']; ?></td>
              <td class="border px-4 py-2"><?php echo $access['user_agent']; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
 </div>
 <script>
    function redirectToDashboard() {
      window.location.href = 'dashboard.php';
    }

    // データを設定します
    var data = {
      labels: <?php echo json_encode(array_column($accesses, 'accessed_at')); ?>,
      datasets: [{
        label: 'アクセス数',
        data: <?php echo json_encode(array_column($accesses, 'access_count')); ?>,
        backgroundColor: 'rgba(144, 238, 144, 0.2)', // 薄い緑色
        borderColor: 'rgba(144, 238, 144, 1)', // 薄い緑色
        borderWidth: 1,
        // データポイント間を線でつなぎます
        fill: false,
        lineTension: 0
      }]
    };

    // オプションを設定します
    var options = {
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            // y軸の単位を整数に制限します
            precision: 0,
            // y軸の単位を5にします
            stepSize: 5
          },
          title: {
            display: true,
            text: 'アクセス数'
          }
        },
        x: {
          title: {
            display: true,
            text: 'アクセス時間'
          }
        }
      },
      elements: {
        point: {
          // データポイントに点を表示します
          radius: 5
        }
      }
    };

    // グラフを作成します
    var ctx = document.getElementById('accessChart').getContext('2d');
    var myChart = new Chart(ctx, {
      type: 'line',
      data: data,
      options: options
    });

   // アクセス日時の並び替えボタンのクリックイベントハンドラ
document.querySelector('.sort-button').addEventListener('click', function() {
    var table = document.getElementById('accessHistoryTable');
    var rows = Array.from(table.rows).slice(1); // ヘッダー行を除く
    var direction = this.getAttribute('data-direction') === 'asc' ? 'desc' : 'asc';
    this.setAttribute('data-direction', direction);
    rows.sort(function(a, b) {
        var aValue = new Date(a.cells[0].textContent);
        var bValue = new Date(b.cells[0].textContent);
        return direction === 'asc' ? aValue - bValue : bValue - aValue;
    });
    rows.forEach(function(row) {
        table.tBodies[0].appendChild(row);
    });
    // フラッシュメッセージを表示
    var message = direction === 'asc' ? "新しい順に並び替えました。" : "古い順に並び替えました。";
    var flashMessage = document.createElement('div');
    flashMessage.textContent = message;
    flashMessage.className = "bg-green-500 text-white p-4 rounded-md mb-4 mt-4 relative";
    document.body.appendChild(flashMessage);
    setTimeout(function() {
        document.body.removeChild(flashMessage);
    }, 3000);
});
 </script>
</body>
</html>
