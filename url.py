from flask import Flask, request, redirect, jsonify, render_template
from hashlib import sha1
from supabase_py import create_client, Client
import os

app = Flask(__name__)

# url = os.getenv("SUPABASE_URL")
# key = os.getenv("SUPABASE_KEY")
url = "SUPABASE_URL"
key = "SUPABASE_KEY"
supabase: Client = create_client(url, key)

@app.route('/', methods=['GET'])
def home():
  return render_template('index.html')

@app.route('/shorten', methods=['POST'])
def shorten_url():
  original_url = request.json['url']
  custom_path = request.json.get('customPath', None)
  if custom_path:
    result = supabase.table("urls").select("hash").filter("hash", "eq", custom_path).execute()
    if result["data"]:
      return jsonify(error="The custom path is already in use"), 400
    hash = custom_path
  else:
    hash = sha1(original_url.encode()).hexdigest()[:8]
  insert_data = {"hash": hash, "url": original_url}
  supabase.table("urls").insert(insert_data)
  return jsonify(short_url=hash), 200

@app.route('/<hash>', methods=['GET'])
def redirect_url(hash):
  result = supabase.table("urls").select("url").filter("hash", "eq", hash).execute()
  if result["data"]:
    return redirect(result["data"][0]["url"])
  else:
    return "URL not found", 404
  
@app.route('/login', methods=['GET'])
def login():
  return render_template('login.html')

@app.route('/signup', methods=['GET'])
def signup():
  return render_template('signup.html')


if __name__ == '__main__':
  app.run(debug=True, port=8000)