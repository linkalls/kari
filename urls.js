document.getElementById('url-form').addEventListener('submit', async function (e) {
  e.preventDefault();
  let url = document.getElementById('url-input').value;
  let customPath = document.getElementById('custom-path-input').value;
  try {
    new URL(url);
  } catch (_) {
    document.getElementById('copyMessage').textContent = '無効なURLです';
    return;
  }
  if (!url.startsWith('http://') && !url.startsWith('https://')) {
    url = 'https://' + url;
  }
  const response = await fetch('/shorten', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ url: url, customPath: customPath })
  });
  const data = await response.json();
  if (data.error) {
    document.getElementById('copyMessage').textContent = 'そのパスは指定できません';
    return;
  }
  const shortUrl = data.short_url;
  document.getElementById('copyMessage').textContent = '短縮URL: ' + shortUrl + ' (生成に成功しました)';
  try {
    await navigator.clipboard.writeText(shortUrl);
    document.getElementById('copyMessage').textContent += ' (クリップボードにコピーしました)';
  } catch (err) {
    document.getElementById('copyMessage').textContent = 'テキストのコピーに失敗しました';
  }
});