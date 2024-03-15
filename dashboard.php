<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["user_id"] == 0) {
 header("Location: login.php");
 exit;
}

include 'db.php';

$userId = $_SESSION["user_id"];

$sql = "SELECT u.original_url, u.short_url, u.created_at, COUNT(a.accessed_at) as access_count 
 FROM short_urls u 
 LEFT JOIN url_accesses a ON u.id = a.short_url_id
 WHERE u.user_id = ? AND u.user_id != 0
 GROUP BY u.original_url, u.short_url, u.created_at";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();

$result = $stmt->get_result();
$urls = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// データを並び替え
$sortOrder = $_GET['sortOrder'] ?? 'created_at';
$sortDirection = $_GET['sortDirection'] ?? 'asc';
usort($urls, function($a, $b) use ($sortOrder, $sortDirection) {
  $result = 0;
  if ($sortOrder === 'created_at') {
    $result = strtotime($a['created_at']) - strtotime($b['created_at']);
  } elseif ($sortOrder === 'access_count') {
    $result = $a['access_count'] - $b['access_count'];
  } elseif ($sortOrder === 'original_url') {
    $result = strcmp($a['original_url'], $b['original_url']);
  } elseif ($sortOrder === 'short_url') {
    $result = strcmp($a['short_url'], $b['short_url']);
  }
  return $sortDirection === 'asc' ? $result : -$result;
});
?>

<!DOCTYPE html>
<html lang="ja">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
 <title>ダッシュボード</title>
 <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
 <script src="https://cdn.jsdelivr.net/npm/chart.js@3.5.1/dist/chart.min.js"></script>
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body class="bg-gray-100 p-4 sm:p-10">
 <div class="container mx-auto flex flex-col items-center justify-center min-h-screen">
    <h1 class="text-2xl sm:text-3xl font-bold mb-4 text-center mt-10">ダッシュボードへようこそ</h1>
    <div class="w-full sm:w-96 bg-white rounded-lg shadow-lg p-6 mb-8 mx-auto">
      <select id="periodSelect" class="mb-4 block w-full">
        <option value="daily">日別</option>
        <option value="monthly">月別</option>
      </select>
      <?php if (count($urls) === 0): ?>
        <p>まだデータがありません。</p>
        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mx-auto block" onclick="redirectToIndex()">
          短縮URLを作成してみよう
        </button>
        <?php else: ?>
      <canvas id="urlAnalysisChart"></canvas>
      <?php endif; ?>
    </div>
    <?php if (count($urls) > 0): ?>
    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mx-auto block" onclick="redirectToIndex()">
      短縮URLを作成する
    </button>
    <div class="flex flex-col">
  <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
    <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
      <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 text-xs sm:text-sm overflow-auto">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-1 sm:px-2 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider w-1/4 break-all">
                元URL
              </th>
              <th scope="col" class="px-1 sm:px-2 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider w-1/4">
                短縮URL
              </th>
              <th scope="col" class="px-1 sm:px-2 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider w-1/4">
                作成日
              </th>
              <th scope="col" class="px-1 sm:px-2 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider w-1/4">
                アクセス数
              </th>
              <th scope="col" class="px-1 sm:px-2 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 uppercase tracking-wider w-1/4">
                詳細
              </th>
            </tr>
          </thead>
          <?php if (count($urls) > 0): ?>
          <tbody>
            <?php foreach ($urls as $url): ?>
            <tr>
              <td class="border px-1 sm:px-2 py-2 word-break break-all text-xs sm:text-sm"><?php echo $url['original_url']; ?></td>
              <td class="border px-1 sm:px-2 py-2 text-xs sm:text-sm overflow-auto"><?php echo $url['short_url']; ?></td>
              <td class="border px-1 sm:px-2 py-2 text-xs sm:text-sm overflow-auto"><?php echo $url['created_at']; ?></td>
              <td class="border px-1 sm:px-2 py-2 text-xs sm:text-sm overflow-auto"><?php echo $url['access_count']; ?></td>
              <td class="border px-1 sm:px-2 py-2 text-xs sm:text-sm overflow-auto"><a href="details.php?short_url=<?php echo $url['short_url']; ?>" style="color:  blue;">詳細へ</a></td>
              <?php endforeach; ?>
              </tbody>
              <?php endif; ?>
              </table>
              </div>
              </div>
              </div>
              </div>
              <script>
              function redirectToIndex() {
                window.location.href = 'index.php';
              }
              
              </script>
            </tr>
          </tbody>
</table>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<script>
    function redirectToIndex() {
      window.location.href = 'index.php';
    }

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

  // index.phpにリダイレクトする urlない時
    function redirectToIndex() {
    window.location.href = 'index.php';
}
</script>
</body>
</html>