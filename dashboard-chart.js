fetch('get_accesses.php')
  .then(response => response.json())
  .then(data => {
    new Chart(document.getElementById('urlAnalysisChart').getContext('2d'), {
      type: 'line',
      data: {
        labels: data.map(item => item.accessed_at),
        datasets: [{
          label: 'アクセス数',
          data: data.map(item => item.count),
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