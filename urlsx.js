// サインアップフォームの送信を処理
document.getElementById('signup-form').addEventListener('submit', function(event) {
  event.preventDefault();
  const username = document.getElementById('username').value;
  const password = document.getElementById('password').value;

  fetch('signup.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`,
  })
  .then(response => response.text())
  .then(data => {
    if (data.includes('Location: login.php')) {
      window.location.href = 'login.php';
    } else {
      alert('サインアップに失敗しました。');
    }
  })
  .catch(error => console.error('Error:', error));
});

// ログインフォームの送信を処理
document.getElementById('login-form').addEventListener('submit', function(event) {
  event.preventDefault();
  const username = document.getElementById('username').value;
  const password = document.getElementById('password').value;

  fetch('login.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`,
  })
  .then(response => response.text())
  .then(data => {
    if (data.includes('Location: index.php')) {
      window.location.href = 'index.php';
    } else {
      alert('ログインに失敗しました。');
    }
  })
  .catch(error => console.error('Error:', error));
});

// URL短縮フォームの送信を処理
document.getElementById('url-form').addEventListener('submit', function(event) {
  event.preventDefault();
  const originalUrl = document.getElementById('url-input').value;
  const customPath = document.getElementById('custom-path-input').value;

  fetch('shorturl.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `url=${encodeURIComponent(originalUrl)}&customPath=${encodeURIComponent(customPath)}`,
  })
  .then(response => response.json())
  .then(data => {
    if (data.short_url) {
      alert(`短縮URL: ${data.short_url}`);
    } else {
      alert('URLの短縮に失敗しました。');
    }
  })
  .catch(error => console.error('Error:', error));
});

// クリップボードにコピーする関数
function copyToClipboard() {
  const copyText = document.getElementById("copyMessage").textContent;
  navigator.clipboard.writeText(copyText).then(() => {
    alert("URLがクリップボードにコピーされました");
  }, (error) => {
    alert("エラー: URLをコピーできませんでした");
  });
}
// URLを短縮する関数
function shortenUrl() {
  const originalUrl = document.getElementById('url').value;
  const customPath = document.getElementById('customPath').value;

  fetch('shorturl.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `url=${encodeURIComponent(originalUrl)}&customPath=${encodeURIComponent(customPath)}`,
  })
  .then(response => response.json())
  .then(data => {
    if (data.short_url) {
      document.getElementById('copyMessage').textContent = data.short_url;
      copyToClipboard();
    } else {
      alert('URLの短縮に失敗しました。');
    }
  })
  .catch(error => {
    console.error('Error:', error);
  });
}
// ボタンのクリックを処理
document.getElementById('shorten-button').addEventListener('click', function(event) {
  event.preventDefault();
  shortenUrl();
});

