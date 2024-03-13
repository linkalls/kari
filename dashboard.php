<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}

// db.phpをインクルード
include 'db.php';

// ユーザーIDを取得
$userId = $_SESSION["user_id"];

// ユーザーが作成したURLの情報を取得
$sql = "SELECT u.original_url, u.short_url, u.created_at, COUNT(a.accessed_at) as access_count 
  FROM short_urls u 
  LEFT JOIN url_accesses a ON u.id = a.short_url_id 
  WHERE u.user_id = ? 
  GROUP BY u.original_url, u.short_url, u.created_at";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();

// 結果を取得
$result = $stmt->get_result();
$urls = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// アクセス数のデータを取得
$accessCounts = array_column($urls, 'access_count');
$accessCountsJson = json_encode($accessCounts);

// 月のデータを取得
$months = array_map(function($url) {
  return date('n月', strtotime($url['created_at']));
}, $urls);
$monthsJson = json_encode($months);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ダッシュボード</title>
  <!-- Tailwind CSS -->
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.5.1/dist/chart.min.js"></script>
  <!-- jQuery -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>
  <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100 px-4 sm:px-0">
    <h1 class="text-3xl font-bold mb-4">ダッシュボードへようこそ</h1>
    <div class="w-full sm:w-96 bg-white rounded-lg shadow-lg p-6 mb-8">
      <!-- 期間選択ドロップダウン -->
      <select id="periodSelect" class="mb-4">
        <option value="daily">日別</option>
        <option value="monthly">月別</option>
      </select>
      <canvas id="urlAnalysisChart"></canvas>
    </div>
    <script>
      function redirectToIndex() {
        window.location.href = 'index.php';
      }
    </script>
    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" onclick="redirectToIndex()">
      短縮URLを作成する
    </button>

    <!-- URL情報のテーブル -->
    <div class="overflow-x-auto">
      <table class="table-auto">
        <thead>
          <tr>
            <th class="px-4 py-2">元URL</th>
            <th class="px-4 py-2">短縮URL</th>
            <th class="px-4 py-2">作成日</th>
            <th class="px-4 py-2">アクセス数</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($urls as $url): ?>
          <tr>
            <td class="border px-4 py-2"><?php echo $url['original_url']; ?></td>
            <td class="border px-4 py-2"><?php echo $url['short_url']; ?></td>
            <td class="border px-4 py-2"><?php echo $url['created_at']; ?></td>
            <td class="border px-4 py-2"><?php echo $url['access_count']; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    // URL Analysis Chart
    let chart;
    const ctx = document.getElementById('urlAnalysisChart').getContext('2d');

    function updateChart(period) {
      if (chart) {
        chart.destroy();
      }
      $.getJSON('get_data.php', { period: period }, function(data) {
        chart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: data.labels,
            datasets: [{
              label: 'アクセス数',
              data: data.accessCounts,
              backgroundColor: 'rgba(75, 192, 192, 0.2)',
              borderColor: 'rgba(75, 192, 192, 1)',
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            scales: {
              y: {
                beginAtZero: true
              }
            }
          }
        });
      });
    }

    $('#periodSelect').change(function() {
      updateChart($(this).val());
    });

    updateChart('daily');
  </script>
</body>
</html>