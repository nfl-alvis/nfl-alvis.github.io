<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Halaman Tambag Makanan PusakaRasa</title>

    <style>
      body {
        margin: 0;
        font-family: "Inter", sans-serif;
        background: #f9f5f0;
        color: #333;
      }

      .bar-kiri {
        width: 250px;
        height: 100vh;
        background: rgba(255, 255, 255, 0.72);
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        padding: 30px 20px;
        position: fixed;
      }

      .logo {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 40px;
      }

      .menu-btn {
        width: 100%;
        padding: 12px 18px;
        background: linear-gradient(
          90deg,
          rgba(224, 107, 76, 0.18),
          rgba(144, 199, 144, 0.18)
        );
        color: rgb(224, 107, 76);
        border-radius: 50px;
        margin-bottom: 10px;
        border: none;
        text-align: left;
        font-size: 15px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .logout {
        background: rgba(220, 38, 38, 0.06);
        color: rgb(185, 28, 28);
        margin-top: 20px;
      }

      .bar-atas {
        margin-left: 290px;
        height: 70px;
        background: #ffffff;
        color: #3e6b48;
        display: flex;
        align-items: center;
        padding-left: 20px;
        font-size: 26px;
        font-weight: 300;
        box-shadow: none;
        position: relative;
        z-index: 1;
      }

      .main {
        margin-left: 250px;
        padding: 20px 60px 40px;
      }

      h1 {
        font-size: 26px;
        margin-bottom: 25px;
        font-weight: 600;
      }

      h2 {
        font-size: 20px;
        font-weight: 600;
        color: #3e6b48;
      }

      .container {
        display: flex;
        gap: 25px;
      }

      .form-card {
        width: 420px;
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      }

      label {
        font-size: 13px;
        font-weight: 500;
        display: block;
        margin: 12px 0 5px;
      }

      input,
      textarea,
      select {
        width: 100%;
        padding: 10px 12px;
        border-radius: 6px;
        border: 1px solid #ddd;
        font-size: 14px;
        background: #fafafa;
      }

      textarea {
        resize: none;
        height: 70px;
      }

      .submit-btn {
        margin-top: 20px;
        background: linear-gradient(
          90deg,
          rgb(224, 107, 7),
          rgb(144, 199, 144)
        );
        padding: 10px 18px;
        border-radius: 50px;
        border: none;
        cursor: pointer;
        font-size: 14px;
        width: 100px;
      }

      .preview {
        width: 500px;
        height: auto;
        max-height: fit-content;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        position: relative;
      }

      .tombol-preview {
        position: absolute;
        background: #e67e22;
        color: white;
        font-size: 12px;
        padding: 4px 12px;
        border-radius: 50px;
        margin: 12px;
        cursor: pointer;
        z-index: 2;
      }

      .gambar-preview {
        width: 100%;
        height: 200px;
        object-fit: cover;
      }

      .body-preview {
        padding: 20px;
      }

      .judul-preview {
        font-size: 17px;
        font-weight: 600;
      }

      .harga-preview {
        color: #d9534f;
        font-weight: 600;
        margin-top: 6px;
      }

      .teks-preview {
        margin-top: 6px;
        font-size: 13px;
        line-height: 1.4;
      }
    </style>
  </head>
  <body>
    <div class="bar-kiri">
      <div class="logo">PusakaRasa</div>
      <button class="menu-btn">Dashboard</button>
      <button class="menu-btn logout">Keluar</button>
    </div>

    <div class="bar-atas">Tambah Makanan</div>

    <div class="main">
      <div class="container">
        <div class="form-card">
          <h2>Data Makanan</h2>

          <label>Nama Makanan</label>
          <input placeholder="Masukkan nama makanan" />

          <label>Deskripsi Singkat</label>
          <textarea placeholder="Tulis deskripsi singkat..."></textarea>

          <label>Sejarah Singkat</label>
          <textarea placeholder="Tulis sejarah singkat..."></textarea>

          <label>Harga</label>
          <input placeholder="Rp. xxxxxx" />

          <label>Kategori</label>
          <select>
            <option>Makanan</option>
          </select>

          <label>Asal Daerah</label>
          <input placeholder="Contoh: Sumatera Barat" />

          <label>Upload Foto</label>
          <input type="file" />

          <label>Instagram</label>
          <input placeholder="Link Instagram" />

          <label>GrabFood</label>
          <input placeholder="Link GrabFood" />

          <label>Google Maps</label>
          <input placeholder="Link Google Maps" />

          <button class="submit-btn">Simpan</button>
        </div>

        <div class="preview">
          <div class="tombol-preview">PREVIEW</div>
          <img class="gambar-preview" src="Image/Image1.png" />
          <div class="body-preview">
            <div class="judul-preview">Nama Makanan</div>
            <div class="teks-preview">Asal Daerah</div>
            <div class="harga-preview">Rp 50.000</div>
            <div class="teks-preview">
              Deskripsi singkat makanan akan tampil di sini untuk memberikan
              gambaran kepada admin sebelum disimpan.
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
