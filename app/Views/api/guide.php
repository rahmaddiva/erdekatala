<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panduan Integrasi API - Sikada Tala</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&family=Source+Sans+3:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root{
    --ink:#0f1923; --ink-2:#1a2733;
    --primary:#dd4814; --primary-d:#b83a10;
    --paper:#f7f5f1; --paper-2:#ffffff;
    --line:#e5e1d8; --muted:#6b7280;
}
body{background:var(--paper);font-family:"Source Sans 3",sans-serif;color:var(--ink);}
h1,h2,h3,h4,h5{font-family:"Libre Baskerville",serif;}

.guide-header{
    background:var(--ink);color:#fff;padding:28px 0;
    border-bottom:3px solid var(--primary);
    box-shadow:0 2px 12px rgba(0,0,0,0.2);
}
.guide-header .brand{display:flex;align-items:center;gap:14px;}
.guide-header .brand-logo{
    height:44px;width:auto;flex-shrink:0;
}
.guide-header h1{font-size:1.7rem;font-weight:700;margin:0;}
.guide-header p{opacity:0.7;font-weight:300;margin-bottom:0;}
.guide-header .btn-light{background:#fff;color:var(--ink);border:none;font-weight:600;}
.guide-header .btn-light:hover{background:#eee;}
.guide-header .btn-warning{background:var(--primary);color:#fff;border:none;font-weight:600;}
.guide-header .btn-warning:hover{background:var(--primary-d);color:#fff;}

.step-badge{
    background:var(--primary);color:#fff;width:32px;height:32px;border-radius:50%;
    display:inline-flex;align-items:center;justify-content:center;
    font-weight:700;font-size:.9rem;margin-right:10px;flex-shrink:0;
}
.step-card{border:1px solid var(--line);border-radius:10px;box-shadow:0 1px 4px rgba(15,25,35,.06);margin-bottom:24px;background:var(--paper-2);}
.step-card .card-header{background:var(--paper-2);border-bottom:2px solid var(--line);border-radius:10px 10px 0 0!important;padding:16px 20px;display:flex;align-items:center;}
.step-card .card-header h5{margin:0;font-weight:700;color:var(--ink);}
pre code{font-size:.82rem;border-radius:6px;}
.nav-lang .nav-link{color:var(--primary);border-radius:6px 6px 0 0;font-weight:600;}
.nav-lang .nav-link.active{background:var(--primary);color:#fff;}
.sidebar{position:sticky;top:20px;}
.sidebar .nav-link{color:#555;padding:6px 12px;border-left:2px solid transparent;font-weight:500;}
.sidebar .nav-link:hover,.sidebar .nav-link.active{color:var(--primary);border-left:2px solid var(--primary);}
.base-url-box{background:rgba(15,25,35,0.04);border:1px solid var(--line);border-radius:6px;padding:12px 16px;font-family:monospace;font-size:.9rem;color:var(--ink);}
.key-box{background:rgba(221,72,20,0.06);border:1px solid rgba(221,72,20,0.25);border-radius:6px;padding:12px 16px;font-family:monospace;font-size:.88rem;color:var(--ink);}
.card{background:var(--paper-2);border:1px solid var(--line);}
.table-bordered{border:1px solid var(--line);}
.table thead th{background:rgba(15,25,35,0.04);color:var(--muted);border-bottom:2px solid var(--line);font-weight:600;}
.table td,.table th{border-top:1px solid var(--line);}
.alert-warning{background:rgba(221,72,20,0.08);border:1px solid rgba(221,72,20,0.25);color:var(--ink);}
.alert-warning hr{border-top-color:rgba(221,72,20,0.2);}
.btn-primary{background:var(--primary);border-color:var(--primary);color:#fff;font-weight:600;}
.btn-primary:hover{background:var(--primary-d);border-color:var(--primary-d);}
.btn-success{background:var(--primary);border-color:var(--primary);color:#fff;font-weight:600;}
.btn-success:hover{background:var(--primary-d);border-color:var(--primary-d);}
.badge-success{background:var(--primary);color:#fff;}
.guide-footer{
    background:var(--ink);color:rgba(255,255,255,0.55);text-align:center;
    padding:22px;font-size:0.83rem;border-top:3px solid var(--primary);line-height:1.7;margin-top:40px;
}
.guide-footer a{color:var(--primary);text-decoration:none;font-weight:600;}
.guide-footer a:hover{text-decoration:underline;}
</style>
</head>
<body>

<div class="guide-header">
<div class="container">
<div class="row align-items-center">
<div class="col-md-8">
<div class="brand">
<img src="/assets/dist/img/Sikadaputih.png" alt="Sikada Tala" class="brand-logo">
<div>
<h1>Panduan Integrasi API Sikada Tala</h1>
<p class="mb-0">Panduan lengkap mengambil dan menampilkan data kependudukan Tanah Laut ke website Anda</p>
</div>
</div>
</div>
<div class="col-md-4 text-md-right mt-3 mt-md-0">
<a href="/api/docs" class="btn btn-light btn-sm mr-2"><i class="fas fa-book mr-1"></i>Dokumentasi API</a>
<a href="/api/register" class="btn btn-warning btn-sm"><i class="fas fa-key mr-1"></i>Dapatkan API Key</a>
</div>
</div>
</div>
</div>

<div class="container mt-4">
<div class="row">

<div class="col-md-3 d-none d-md-block">
<div class="sidebar card p-3">
<div class="font-weight-bold text-muted small mb-2 text-uppercase">Daftar Isi</div>
<nav class="nav flex-column" id="sidebar-nav">
<a class="nav-link" href="#step1">1. Dapatkan API Key</a>
<a class="nav-link" href="#step2">2. Base URL dan Endpoint</a>
<a class="nav-link" href="#step3">3. Contoh PHP</a>
<a class="nav-link" href="#step4">4. Contoh JavaScript</a>
<a class="nav-link" href="#step5">5. Contoh Python</a>
<a class="nav-link" href="#step6">6. Contoh React</a>
<a class="nav-link" href="#step7">7. Contoh Vue.js</a>
<a class="nav-link" href="#step8">8. Error Handling</a>
</nav>
</div>
</div>

<div class="col-md-9">

<div class="step-card card" id="step1">
<div class="card-header"><span class="step-badge">1</span><h5>Dapatkan API Key</h5></div>
<div class="card-body">
<p>Sebelum menggunakan API, daftarkan diri untuk mendapatkan API key gratis dengan limit <strong>1000 request/hari</strong>.</p>
<ol>
<li>Buka halaman <a href="/api/register" target="_blank"><strong>/api/register</strong></a></li>
<li>Isi nama, email, dan label key Anda</li>
<li>Salin API key yang muncul dan simpan dengan aman</li>
</ol>
<div class="key-box">
<strong>Contoh API Key:</strong><br>
<code>a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2</code>
</div>
<div class="alert alert-warning mt-3 mb-0">
<i class="fas fa-exclamation-triangle mr-2"></i>API key hanya ditampilkan sekali. Jangan simpan di kode yang bersifat publik (GitHub, dll).
</div>
</div>
</div>

<div class="step-card card" id="step2">
<div class="card-header"><span class="step-badge">2</span><h5>Base URL dan Endpoint</h5></div>
<div class="card-body">
<div class="base-url-box mb-3"><strong>Base URL:</strong> https://sikadatala.test/api/v1</div>
<table class="table table-bordered table-sm">
<thead class="thead-light"><tr><th>Method</th><th>Endpoint</th><th>Keterangan</th></tr></thead>
<tbody>
<tr><td><span class="badge badge-success">GET</span></td><td><code>/kecamatan</code></td><td>Daftar semua kecamatan</td></tr>
<tr><td><span class="badge badge-success">GET</span></td><td><code>/desa?id_kecamatan=1</code></td><td>Daftar desa (filter opsional)</td></tr>
<tr><td><span class="badge badge-success">GET</span></td><td><code>/laporan</code></td><td>Data agregat per RT (paginasi)</td></tr>
<tr><td><span class="badge badge-success">GET</span></td><td><code>/laporan/rekap/kecamatan</code></td><td>Rekap total per kecamatan</td></tr>
<tr><td><span class="badge badge-success">GET</span></td><td><code>/laporan/rekap/desa?id_kecamatan=1</code></td><td>Rekap per desa</td></tr>
</tbody>
</table>
<p class="mb-1"><strong>Cara kirim API key (pilih salah satu):</strong></p>
<div class="row">
<div class="col-md-6">
<div class="key-box"><strong>Header (direkomendasikan):</strong><br><code>Authorization: Bearer YOUR_API_KEY</code></div>
</div>
<div class="col-md-6">
<div class="key-box"><strong>Query Parameter:</strong><br><code>?api_key=YOUR_API_KEY</code></div>
</div>
</div>
</div>
</div>

<div class="step-card card" id="step3">
<div class="card-header"><span class="step-badge">3</span><h5>Contoh PHP</h5></div>
<div class="card-body">
<ul class="nav nav-tabs nav-lang mb-3" id="phpTab">
<li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#php-curl">cURL</a></li>
<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#php-guzzle">Guzzle</a></li>
<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#php-display">Tampilkan di Halaman</a></li>
</ul>
<div class="tab-content">
<div class="tab-pane active" id="php-curl">
<pre><code class="language-php">&lt;?php
define('API_KEY', 'YOUR_API_KEY');
define('API_BASE', 'https://sikadatala.test/api/v1');

function apiGet(string $endpoint, array $params = []): array {
    $url = API_BASE . $endpoint;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER =&gt; true,
        CURLOPT_HTTPHEADER     =&gt; ['Authorization: Bearer ' . API_KEY],
        CURLOPT_TIMEOUT        =&gt; 10,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true) ?? [];
}

// Ambil daftar kecamatan
$kecamatan = apiGet('/kecamatan');
print_r($kecamatan);

// Ambil rekap per kecamatan
$rekap = apiGet('/laporan/rekap/kecamatan');
print_r($rekap);
?&gt;</code></pre>
</div>
<div class="tab-pane" id="php-guzzle">
<pre><code class="language-php">&lt;?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client([
    'base_uri' =&gt; 'https://sikadatala.test/api/v1/',
    'headers'  =&gt; ['Authorization' =&gt; 'Bearer YOUR_API_KEY'],
    'timeout'  =&gt; 10,
]);

// Ambil kecamatan
$res  = $client-&gt;get('kecamatan');
$data = json_decode($res-&gt;getBody(), true);

foreach ($data['data'] as $kec) {
    echo $kec['nama_kecamatan'] . PHP_EOL;
}

// Ambil laporan dengan filter
$res  = $client-&gt;get('laporan', ['query' =&gt; ['id_kecamatan' =&gt; 1, 'tahun' =&gt; 2025]]);
$data = json_decode($res-&gt;getBody(), true);
echo "Total data: " . $data['total'];
?&gt;</code></pre>
</div>
<div class="tab-pane" id="php-display">
<pre><code class="language-php">&lt;?php
// Ambil dan tampilkan rekap kecamatan dalam tabel HTML
define('API_KEY', 'YOUR_API_KEY');
define('API_BASE', 'https://sikadatala.test/api/v1');

$ch = curl_init(API_BASE . '/laporan/rekap/kecamatan');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . API_KEY]);
$data = json_decode(curl_exec($ch), true);
curl_close($ch);
?&gt;
&lt;table&gt;
  &lt;thead&gt;
    &lt;tr&gt;&lt;th&gt;Kecamatan&lt;/th&gt;&lt;th&gt;Total Jiwa&lt;/th&gt;&lt;th&gt;Total KK&lt;/th&gt;&lt;/tr&gt;
  &lt;/thead&gt;
  &lt;tbody&gt;
    &lt;?php foreach ($data['data'] as $row): ?&gt;
    &lt;tr&gt;
      &lt;td&gt;&lt;?= htmlspecialchars($row['nama_kecamatan']) ?&gt;&lt;/td&gt;
      &lt;td&gt;&lt;?= number_format($row['total_jiwa']) ?&gt;&lt;/td&gt;
      &lt;td&gt;&lt;?= number_format($row['total_kk']) ?&gt;&lt;/td&gt;
    &lt;/tr&gt;
    &lt;?php endforeach; ?&gt;
  &lt;/tbody&gt;
&lt;/table&gt;</code></pre>
</div>
</div>
</div>
</div>

<div class="step-card card" id="step4">
<div class="card-header"><span class="step-badge">4</span><h5>Contoh JavaScript (Vanilla / jQuery)</h5></div>
<div class="card-body">
<ul class="nav nav-tabs nav-lang mb-3" id="jsTab">
<li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#js-fetch">Fetch API</a></li>
<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#js-jquery">jQuery Ajax</a></li>
</ul>
<div class="tab-content">
<div class="tab-pane active" id="js-fetch">
<pre><code class="language-javascript">const API_KEY  = 'YOUR_API_KEY';
const API_BASE = 'https://sikadatala.test/api/v1';

async function apiGet(endpoint, params = {}) {
    const url = new URL(API_BASE + endpoint);
    Object.entries(params).forEach(([k, v]) =&gt; url.searchParams.set(k, v));

    const res = await fetch(url, {
        headers: { 'Authorization': 'Bearer ' + API_KEY }
    });

    if (!res.ok) throw new Error('API Error: ' + res.status);
    return res.json();
}

// Tampilkan daftar kecamatan ke tabel
async function loadKecamatan() {
    const data = await apiGet('/kecamatan');
    const tbody = document.querySelector('#tabelKecamatan tbody');
    tbody.innerHTML = data.data.map(k =&gt; `
        &lt;tr&gt;
            &lt;td&gt;${k.id_kecamatan}&lt;/td&gt;
            &lt;td&gt;${k.nama_kecamatan}&lt;/td&gt;
            &lt;td&gt;${k.kode_kecamatan}&lt;/td&gt;
        &lt;/tr&gt;
    `).join('');
}

// Tampilkan rekap dengan chart
async function loadRekap() {
    const data = await apiGet('/laporan/rekap/kecamatan');
    const labels = data.data.map(d =&gt; d.nama_kecamatan);
    const values = data.data.map(d =&gt; parseInt(d.total_jiwa));

    // Render ke Chart.js
    new Chart(document.getElementById('chartJiwa'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{ label: 'Total Jiwa', data: values, backgroundColor: '#dd4814' }]
        }
    });
}

loadKecamatan();
loadRekap();</code></pre>
</div>
<div class="tab-pane" id="js-jquery">
<pre><code class="language-javascript">const API_KEY  = 'YOUR_API_KEY';
const API_BASE = 'https://sikadatala.test/api/v1';

// Ambil rekap kecamatan
$.ajax({
    url: API_BASE + '/laporan/rekap/kecamatan',
    headers: { 'Authorization': 'Bearer ' + API_KEY },
    success: function(data) {
        let html = '';
        $.each(data.data, function(i, row) {
            html += `&lt;tr&gt;
                &lt;td&gt;${row.nama_kecamatan}&lt;/td&gt;
                &lt;td&gt;${parseInt(row.total_jiwa).toLocaleString()}&lt;/td&gt;
                &lt;td&gt;${parseInt(row.total_kk).toLocaleString()}&lt;/td&gt;
            &lt;/tr&gt;`;
        });
        $('#tabelRekap tbody').html(html);
    },
    error: function(xhr) {
        console.error('Error:', xhr.responseJSON.message);
    }
});

// Ambil desa berdasarkan kecamatan
function loadDesa(idKecamatan) {
    $.ajax({
        url: API_BASE + '/desa',
        data: { api_key: API_KEY, id_kecamatan: idKecamatan },
        success: function(data) {
            console.log('Total desa:', data.total);
        }
    });
}</code></pre>
</div>
</div>
</div>
</div>

<div class="step-card card" id="step5">
<div class="card-header"><span class="step-badge">5</span><h5>Contoh Python</h5></div>
<div class="card-body">
<pre><code class="language-python">import requests

API_KEY  = "YOUR_API_KEY"
API_BASE = "https://sikadatala.test/api/v1"
HEADERS  = {"Authorization": f"Bearer {API_KEY}"}

def api_get(endpoint, params=None):
    url = API_BASE + endpoint
    res = requests.get(url, headers=HEADERS, params=params, timeout=10)
    res.raise_for_status()
    return res.json()

# Ambil semua kecamatan
kecamatan = api_get("/kecamatan")
for k in kecamatan["data"]:
    print(f"{k['kode_kecamatan']} - {k['nama_kecamatan']}")

# Ambil laporan dengan filter
laporan = api_get("/laporan", params={"id_kecamatan": 1, "tahun": 2025, "per_page": 50})
print(f"Total data: {laporan['total']}, Halaman: {laporan['pages']}")

# Simpan ke DataFrame (pandas)
import pandas as pd

rekap = api_get("/laporan/rekap/kecamatan")
df = pd.DataFrame(rekap["data"])
df["total_jiwa"] = df["total_jiwa"].astype(int)
df["total_kk"]   = df["total_kk"].astype(int)
print(df[["nama_kecamatan", "total_jiwa", "total_kk"]])
df.to_csv("rekap_kependudukan.csv", index=False)
print("Data disimpan ke rekap_kependudukan.csv")</code></pre>
</div>
</div>

<div class="step-card card" id="step6">
<div class="card-header"><span class="step-badge">6</span><h5>Contoh React</h5></div>
<div class="card-body">
<pre><code class="language-jsx">import { useState, useEffect } from "react";

const API_KEY  = process.env.REACT_APP_SIKADA_KEY;
const API_BASE = "https://sikadatala.test/api/v1";

async function apiGet(endpoint, params = {}) {
    const url = new URL(API_BASE + endpoint);
    Object.entries(params).forEach(([k, v]) =&gt; url.searchParams.set(k, v));
    const res = await fetch(url, {
        headers: { Authorization: "Bearer " + API_KEY },
    });
    if (!res.ok) throw new Error(await res.text());
    return res.json();
}

export function TabelKecamatan() {
    const [data, setData]     = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError]   = useState(null);

    useEffect(() =&gt; {
        apiGet("/laporan/rekap/kecamatan")
            .then(r =&gt; setData(r.data))
            .catch(e =&gt; setError(e.message))
            .finally(() =&gt; setLoading(false));
    }, []);

    if (loading) return &lt;p&gt;Memuat data...&lt;/p&gt;;
    if (error)   return &lt;p style={{color:"red"}}&gt;Error: {error}&lt;/p&gt;;

    return (
        &lt;table&gt;
            &lt;thead&gt;&lt;tr&gt;&lt;th&gt;Kecamatan&lt;/th&gt;&lt;th&gt;Total Jiwa&lt;/th&gt;&lt;th&gt;Total KK&lt;/th&gt;&lt;/tr&gt;&lt;/thead&gt;
            &lt;tbody&gt;
                {data.map(row =&gt; (
                    &lt;tr key={row.nama_kecamatan}&gt;
                        &lt;td&gt;{row.nama_kecamatan}&lt;/td&gt;
                        &lt;td&gt;{parseInt(row.total_jiwa).toLocaleString()}&lt;/td&gt;
                        &lt;td&gt;{parseInt(row.total_kk).toLocaleString()}&lt;/td&gt;
                    &lt;/tr&gt;
                ))}
            &lt;/tbody&gt;
        &lt;/table&gt;
    );
}</code></pre>
<div class="alert alert-info mb-0 mt-3">
<i class="fas fa-info-circle mr-2"></i>Simpan API key di file <code>.env</code>: <code>REACT_APP_SIKADA_KEY=your_key_here</code>
</div>
</div>
</div>

<div class="step-card card" id="step7">
<div class="card-header"><span class="step-badge">7</span><h5>Contoh Vue.js</h5></div>
<div class="card-body">
<pre><code class="language-html">&lt;template&gt;
  &lt;div&gt;
    &lt;div v-if="loading"&gt;Memuat data...&lt;/div&gt;
    &lt;div v-else-if="error" class="error"&gt;{{ error }}&lt;/div&gt;
    &lt;table v-else&gt;
      &lt;thead&gt;&lt;tr&gt;&lt;th&gt;Kecamatan&lt;/th&gt;&lt;th&gt;Total Jiwa&lt;/th&gt;&lt;th&gt;Total KK&lt;/th&gt;&lt;/tr&gt;&lt;/thead&gt;
      &lt;tbody&gt;
        &lt;tr v-for="row in rekap" :key="row.nama_kecamatan"&gt;
          &lt;td&gt;{{ row.nama_kecamatan }}&lt;/td&gt;
          &lt;td&gt;{{ Number(row.total_jiwa).toLocaleString() }}&lt;/td&gt;
          &lt;td&gt;{{ Number(row.total_kk).toLocaleString() }}&lt;/td&gt;
        &lt;/tr&gt;
      &lt;/tbody&gt;
    &lt;/table&gt;
  &lt;/div&gt;
&lt;/template&gt;

&lt;script&gt;
export default {
  data() {
    return { rekap: [], loading: true, error: null };
  },
  async created() {
    try {
      const res = await fetch("https://sikadatala.test/api/v1/laporan/rekap/kecamatan", {
        headers: { Authorization: "Bearer " + import.meta.env.VITE_API_KEY }
      });
      const json = await res.json();
      this.rekap = json.data;
    } catch (e) {
      this.error = e.message;
    } finally {
      this.loading = false;
    }
  }
};
&lt;/script&gt;</code></pre>
<div class="alert alert-info mb-0 mt-3">
<i class="fas fa-info-circle mr-2"></i>Untuk Vite/Vue 3: simpan di <code>.env</code> sebagai <code>VITE_API_KEY=your_key_here</code>
</div>
</div>
</div>

<div class="step-card card" id="step8">
<div class="card-header"><span class="step-badge">8</span><h5>Error Handling dan Response Code</h5></div>
<div class="card-body">
<table class="table table-bordered table-sm mb-3">
<thead class="thead-light"><tr><th>HTTP Code</th><th>Arti</th><th>Solusi</th></tr></thead>
<tbody>
<tr><td><span class="badge badge-success">200</span></td><td>Berhasil</td><td>Data tersedia di field <code>data</code></td></tr>
<tr><td><span class="badge badge-warning">401</span></td><td>API key tidak valid / tidak ada</td><td>Periksa API key dan cara pengiriman</td></tr>
<tr><td><span class="badge badge-danger">403</span></td><td>API key dinonaktifkan</td><td>Hubungi admin atau daftar key baru</td></tr>
<tr><td><span class="badge badge-danger">429</span></td><td>Limit harian tercapai</td><td>Tunggu hingga esok hari (reset tengah malam)</td></tr>
<tr><td><span class="badge badge-danger">422</span></td><td>Parameter tidak lengkap</td><td>Periksa parameter yang wajib diisi</td></tr>
</tbody>
</table>
<pre><code class="language-javascript">// Contoh error handling lengkap
async function safeApiGet(endpoint, params = {}) {
    try {
        const url = new URL("https://sikadatala.test/api/v1" + endpoint);
        Object.entries(params).forEach(([k, v]) =&gt; url.searchParams.set(k, v));

        const res = await fetch(url, {
            headers: { "Authorization": "Bearer YOUR_API_KEY" }
        });

        const json = await res.json();

        if (!res.ok) {
            if (res.status === 429) alert("Kuota harian habis. Coba lagi besok.");
            else if (res.status === 401) alert("API key tidak valid.");
            else alert("Error: " + json.message);
            return null;
        }

        return json;
    } catch (err) {
        console.error("Network error:", err);
        return null;
    }
}</code></pre>
</div>
</div>

<div class="text-center py-4">
<a href="/api/docs" class="btn btn-primary mr-2"><i class="fas fa-book mr-1"></i>Lihat Dokumentasi Lengkap</a>
<a href="/api/register" class="btn btn-success"><i class="fas fa-key mr-1"></i>Daftar API Key Gratis</a>
</div>

</div>
</div>
</div>

<div class="guide-footer">
    Sikada Tala &middot; Sistem Informasi Kependudukan Kabupaten Tanah Laut, Kalimantan Selatan<br>
    <a href="/api/docs">Dokumentasi</a> &middot; <a href="/api/guide">Panduan</a> &middot; <a href="/api/register">Daftar API Key</a> &middot; <a href="/">Beranda</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script>
hljs.highlightAll();
// Sidebar active link
const links = document.querySelectorAll("#sidebar-nav .nav-link");
window.addEventListener("scroll", () => {
    links.forEach(link => {
        const target = document.querySelector(link.getAttribute("href"));
        if (target) {
            const top = target.getBoundingClientRect().top;
            if (top >= -50 && top <= 200) {
                links.forEach(l => l.classList.remove("active"));
                link.classList.add("active");
            }
        }
    });
});
</script>
</body>
</html>
