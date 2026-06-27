<?= $this->extend('templates/main') ?>
<?= $this->section('content') ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 font-weight-bold"><?= esc($title) ?></h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="<?= base_url('kecamatan') ?>" class="btn btn-secondary btn-sm shadow-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">

                    <?php if (isset($errors)): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <ul class="mb-0">
                                <?php foreach ($errors as $e): ?>
                                    <li><?= esc($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php $isEdit = isset($kec); ?>

                    <form action="<?= $isEdit ? base_url('kecamatan/update/' . $kec['id_kecamatan']) : base_url('kecamatan/store') ?>"
                        method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>

                        <div class="row">
                            <!-- ===== SECTION 1: DATA DASAR ===== -->
                            <div class="col-lg-6">
                                <div class="card card-outline card-primary shadow-sm">
                                    <div class="card-header">
                                        <h3 class="card-title font-weight-bold"><i class="fas fa-map-marked-alt mr-2"></i>Data Dasar</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="nama_kecamatan">Nama Kecamatan <span class="text-danger">*</span></label>
                                            <input type="text" name="nama_kecamatan" id="nama_kecamatan"
                                                class="form-control" required
                                                value="<?= old('nama_kecamatan', $isEdit ? $kec['nama_kecamatan'] : '') ?>"
                                                placeholder="Contoh: Takisung">
                                        </div>
                                        <div class="form-group">
                                            <label for="kode_kecamatan">Kode Kecamatan</label>
                                            <input type="text" name="kode_kecamatan" id="kode_kecamatan"
                                                class="form-control"
                                                value="<?= old('kode_kecamatan', $isEdit ? $kec['kode_kecamatan'] : '') ?>"
                                                placeholder="Contoh: 6301">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== SECTION 3: SEO & VISIBILITAS ===== -->
                            <div class="col-lg-6">
                                <div class="card card-outline card-info shadow-sm">
                                    <div class="card-header">
                                        <h3 class="card-title font-weight-bold"><i class="fas fa-search mr-2"></i>SEO &amp; Visibilitas</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="slug">Slug URL</label>
                                            <input type="text" name="slug" id="slug"
                                                class="form-control"
                                                value="<?= old('slug', $isEdit ? ($kec['slug'] ?? '') : '') ?>"
                                                placeholder="Otomatis dari nama jika dikosongkan">
                                            <small class="form-text text-muted">URL: <code><?= base_url('/') ?><span id="slugPreview"><?= $isEdit ? ($kec['slug'] ?: strtolower(str_replace(' ', '-', $kec['nama_kecamatan']))) : 'otomatis' ?></span></code></small>
                                        </div>
                                        <div class="form-group">
                                            <label for="page_title">Judul Halaman (H1)</label>
                                            <input type="text" name="page_title" id="page_title"
                                                class="form-control"
                                                value="<?= old('page_title', $isEdit ? ($kec['page_title'] ?? '') : '') ?>"
                                                placeholder="Otomatis: Kecamatan {nama} jika dikosongkan">
                                        </div>
                                        <div class="form-group">
                                            <label for="meta_description">Meta Description</label>
                                            <textarea name="meta_description" id="meta_description"
                                                class="form-control" rows="2"
                                                placeholder="Deskripsi singkat untuk mesin pencari (max ~160 karakter)"><?= old('meta_description', $isEdit ? ($kec['meta_description'] ?? '') : '') ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="is_public"
                                                    name="is_public" value="1"
                                                    <?= old('is_public', $isEdit ? ($kec['is_public'] ?? 1) : 1) ? 'checked' : '' ?>>
                                                <label class="custom-control-label" for="is_public">Halaman publik tampil di landing page</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ===== SECTION 2: KONTEN HALAMAN PUBLIK ===== -->
                        <div class="card card-outline card-success shadow-sm">
                            <div class="card-header">
                                <h3 class="card-title font-weight-bold"><i class="fas fa-newspaper mr-2"></i>Konten Halaman Publik</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="form-group">
                                            <label for="deskripsi">Deskripsi Kecamatan</label>
                                            <textarea name="deskripsi" id="deskripsi"
                                                class="form-control" rows="4"
                                                placeholder="Deskripsi singkat tentang kecamatan..."><?= old('deskripsi', $isEdit ? ($kec['deskripsi'] ?? '') : '') ?></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="nama_camat">Nama Camat</label>
                                                    <input type="text" name="nama_camat" id="nama_camat"
                                                        class="form-control"
                                                        value="<?= old('nama_camat', $isEdit ? ($kec['nama_camat'] ?? '') : '') ?>"
                                                        placeholder="Nama lengkap camat">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="jam_layanan">Jam Layanan</label>
                                                    <input type="text" name="jam_layanan" id="jam_layanan"
                                                        class="form-control"
                                                        value="<?= old('jam_layanan', $isEdit ? ($kec['jam_layanan'] ?? '') : '') ?>"
                                                        placeholder="Contoh: Senin-Jumat 08:00-16:00">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="alamat_kantor">Alamat Kantor</label>
                                            <textarea name="alamat_kantor" id="alamat_kantor"
                                                class="form-control" rows="2"
                                                placeholder="Alamat lengkap kantor kecamatan..."><?= old('alamat_kantor', $isEdit ? ($kec['alamat_kantor'] ?? '') : '') ?></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="telepon">Telepon</label>
                                                    <input type="text" name="telepon" id="telepon"
                                                        class="form-control"
                                                        value="<?= old('telepon', $isEdit ? ($kec['telepon'] ?? '') : '') ?>"
                                                        placeholder="Contoh: 0512-123456">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="email">Email</label>
                                                    <input type="email" name="email" id="email"
                                                        class="form-control"
                                                        value="<?= old('email', $isEdit ? ($kec['email'] ?? '') : '') ?>"
                                                        placeholder="Contoh: kec.takisung@tala.go.id">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label>Foto / Kantor Kecamatan</label>
                                            <div class="mb-2">
                                                <?php if ($isEdit && !empty($kec['foto']) && file_exists(FCPATH . $kec['foto'])): ?>
                                                    <img src="<?= base_url($kec['foto']) ?>" alt="Foto"
                                                        class="img-thumbnail mb-2" style="max-height: 200px; width: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center mb-2"
                                                        style="height: 200px; border: 2px dashed #dee2e6;">
                                                        <i class="fas fa-image text-muted" style="font-size: 3rem;"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" name="foto" id="foto"
                                                    accept="image/jpeg,image/png,image/webp">
                                                <label class="custom-file-label" for="foto">Pilih foto (jpg/png/webp, max 2MB)</label>
                                            </div>
                                            <?php if ($isEdit && !empty($kec['foto'])): ?>
                                                <small class="text-muted">Kosongkan jika tidak ingin mengganti foto.</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> <?= $isEdit ? 'Update Data' : 'Simpan Data' ?>
                                </button>
                                <a href="<?= base_url('kecamatan') ?>" class="btn btn-default">Batal</a>
                            </div>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var namaInput = document.getElementById('nama_kecamatan');
    var slugInput = document.getElementById('slug');
    var slugPreview = document.getElementById('slugPreview');

    function autoSlug() {
        if (slugInput && slugInput.value === '') {
            var slug = namaInput.value.toLowerCase().replace(/\s+/g, '-');
            slugPreview.textContent = slug || 'otomatis';
        }
    }

    if (namaInput) {
        namaInput.addEventListener('input', autoSlug);
    }
    if (slugInput) {
        slugInput.addEventListener('input', function() {
            slugPreview.textContent = this.value || (namaInput ? namaInput.value.toLowerCase().replace(/\s+/g, '-') : 'otomatis');
        });
    }

    var fotoInput = document.getElementById('foto');
    if (fotoInput) {
        fotoInput.addEventListener('change', function(e) {
            var fileName = e.target.files[0] ? e.target.files[0].name : 'Pilih foto (jpg/png/webp, max 2MB)';
            var label = document.querySelector('.custom-file-label');
            if (label) label.textContent = fileName;
        });
    }
});
</script>

<?= $this->endSection() ?>
