<div class="offcanvas offcanvas-end overflow-hidden" tabindex="-1" id="theme-settings-offcanvas" aria-labelledby="theme-settings-title">
    <div class="d-flex justify-content-between text-bg-primary gap-2 p-3">
        <div>
            <h5 class="mb-1 fw-bold text-white text-uppercase" id="theme-settings-title">Pengaturan Tampilan</h5>
            <p class="text-white text-opacity-75 fst-italic fw-medium mb-0">Konfigurasi tampilan bawaan UBold.</p>
        </div>
        <button type="button" class="btn btn-sm bg-white bg-opacity-25 text-white rounded-circle btn-icon" data-bs-dismiss="offcanvas" aria-label="Tutup">
            <i data-lucide="x" class="fs-lg"></i>
        </button>
    </div>

    <div class="offcanvas-body theme-customizer-bar p-0 h-100" data-simplebar>
        <div id="theme" class="p-3 border-bottom border-dashed">
            <h5 class="mb-3 fw-bold">Skema warna</h5>
            <div class="row g-3">
                <div class="col-4" id="theme-light">
                    <div class="form-check card-radio">
                        <input class="form-check-input" type="radio" name="data-bs-theme" id="layout-color-light" value="light">
                        <label class="form-check-label p-2 w-100 text-center" for="layout-color-light">Terang</label>
                    </div>
                </div>
                <div class="col-4" id="theme-dark">
                    <div class="form-check card-radio">
                        <input class="form-check-input" type="radio" name="data-bs-theme" id="layout-color-dark" value="dark">
                        <label class="form-check-label p-2 w-100 text-center" for="layout-color-dark">Gelap</label>
                    </div>
                </div>
                <div class="col-4" id="theme-system">
                    <div class="form-check card-radio">
                        <input class="form-check-input" type="radio" name="data-bs-theme" id="layout-color-system" value="system">
                        <label class="form-check-label p-2 w-100 text-center" for="layout-color-system">Sistem</label>
                    </div>
                </div>
            </div>
        </div>

        <div id="sidenav-size" class="p-3 border-bottom border-dashed">
            <h5 class="mb-3 fw-bold">Ukuran sidebar</h5>
            <select class="form-select" name="data-sidenav-size" aria-label="Ukuran sidebar">
                <option value="default">Standar</option>
                <option value="compact">Ringkas</option>
                <option value="condensed">Ikon</option>
            </select>
        </div>

        <div class="p-3">
            <button type="button" class="btn btn-danger fw-semibold py-2 w-100" id="reset-layout">
                <i data-lucide="refresh-ccw" class="me-2 fs-md"></i>Atur Ulang
            </button>
        </div>
    </div>
</div>
