<?php
/**
 * Acil Diyetisyen - Emergency Dietitian Support
 * Admin tarafından yanıtlanacak acil destek talepleri
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Acil Diyetisyen Desteği';
$metaDescription = 'Acil durumlarda uzman ekibimizden anında destek alın. Admin ekibimiz size en kısa sürede yardımcı olacaktır.';
include __DIR__ . '/../includes/partials/header.php';
?>

<style>
    :root {
        --emergency-red: #ef4444;
        --emergency-orange: #f59e0b;
        --primary: #56ab2f;
        --text-dark: #0f172a;
        --text-light: #64748b;
    }

    .emergency-hero {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        padding: 120px 0 80px;
        text-align: center;
        margin-top: 70px;
        position: relative;
        overflow: hidden;
    }

    .emergency-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.1;
    }

    .emergency-hero h1 {
        font-size: 3.5rem;
        font-weight: 800;
        margin-bottom: 1rem;
        position: relative;
        animation: pulse-text 2s infinite;
    }

    .emergency-hero .subtitle {
        font-size: 1.3rem;
        opacity: 0.95;
        margin-bottom: 2rem;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }

    .emergency-badge {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        padding: 1rem 2rem;
        border-radius: 50px;
        display: inline-flex;
        align-items: center;
        gap: 1rem;
        font-size: 1.1rem;
        font-weight: 600;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .emergency-badge i {
        animation: pulse-icon 1.5s infinite;
        font-size: 1.5rem;
    }

    @keyframes pulse-text {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); }
    }

    @keyframes pulse-icon {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.2); opacity: 0.7; }
    }

    .info-section {
        padding: 60px 0;
        background: #fff7ed;
    }

    .info-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        text-align: center;
        height: 100%;
        border: 3px solid #fed7aa;
        transition: all 0.3s;
    }

    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }

    .info-card i {
        font-size: 3rem;
        color: var(--emergency-orange);
        margin-bottom: 1rem;
    }

    .info-card h3 {
        color: var(--text-dark);
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .info-card p {
        color: var(--text-light);
        line-height: 1.7;
    }

    .request-section {
        padding: 80px 0;
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    }

    .request-form-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        border-radius: 28px;
        padding: 3rem;
        box-shadow: 0 20px 60px rgba(0,0,0,0.08);
        border: 3px solid #fee2e2;
    }

    .section-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--text-dark);
        text-align: center;
        margin-bottom: 1rem;
    }

    .section-subtitle {
        color: var(--text-light);
        font-size: 1.1rem;
        text-align: center;
        margin-bottom: 3rem;
    }

    .form-label {
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 0.5rem;
    }

    .form-label .required {
        color: var(--emergency-red);
        margin-left: 0.25rem;
    }

    .form-control, .form-select {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.875rem 1rem;
        font-size: 1rem;
        transition: all 0.3s;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--emergency-red);
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }

    textarea.form-control {
        min-height: 120px;
        resize: vertical;
    }

    .urgency-selector {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .urgency-option {
        position: relative;
    }

    .urgency-option input[type="radio"] {
        position: absolute;
        opacity: 0;
    }

    .urgency-label {
        display: block;
        padding: 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 600;
    }

    .urgency-option input[type="radio"]:checked + .urgency-label {
        border-color: var(--emergency-red);
        background: #fef2f2;
        color: var(--emergency-red);
    }

    .urgency-label:hover {
        border-color: var(--emergency-red);
    }

    .btn-submit-request {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        border: none;
        padding: 1.2rem 3rem;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.1rem;
        width: 100%;
        transition: all 0.3s;
        box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
    }

    .btn-submit-request:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(239, 68, 68, 0.4);
    }

    .alert {
        border-radius: 12px;
        border: none;
        padding: 1rem 1.5rem;
    }

    @media (max-width: 768px) {
        .emergency-hero h1 {
            font-size: 2.5rem;
        }

        .emergency-hero {
            padding: 100px 0 60px;
        }

        .request-form-container {
            padding: 2rem 1.5rem;
        }

        .section-title {
            font-size: 2rem;
        }
    }
</style>

<div class="emergency-hero">
    <div class="container">
        <div class="emergency-badge">
            <i class="fas fa-heartbeat"></i>
            <span>Admin Desteği</span>
        </div>
        <h1 class="mt-4"><i class="fas fa-ambulance me-3"></i>Acil Diyetisyen Desteği</h1>
        <p class="subtitle">
            Acil durumlarda profesyonel ekibimiz size anında yardımcı olacaktır.
            Talebinizi gönderin, admin ekibimiz en kısa sürede size dönüş yapacaktır.
        </p>
    </div>
</div>

<div class="info-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="info-card">
                    <i class="fas fa-user-shield"></i>
                    <h3>Admin Desteği</h3>
                    <p>Acil talepleriniz doğrudan admin ekibimize ulaşır ve öncelikli olarak değerlendirilir.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card">
                    <i class="fas fa-clock"></i>
                    <h3>Hızlı Yanıt</h3>
                    <p>Talebinizin aciliyet durumuna göre en kısa sürede size dönüş yapılır.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Güvenli & Gizli</h3>
                    <p>Tüm bilgileriniz gizlilik politikamız çerçevesinde korunur.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="request-section">
    <div class="container">
        <div class="request-form-container">
            <h2 class="section-title">Acil Destek Talebi</h2>
            <p class="section-subtitle">
                Lütfen aşağıdaki formu eksiksiz doldurun. Ekibimiz talebinizi değerlendirerek size en kısa sürede dönüş yapacaktır.
            </p>

            <div id="alert-container"></div>

            <form id="emergencyRequestForm" method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Ad Soyad<span class="required">*</span>
                        </label>
                        <input type="text"
                               class="form-control"
                               name="full_name"
                               required
                               value="<?= $auth->check() ? clean($auth->user()['full_name']) : '' ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            E-posta<span class="required">*</span>
                        </label>
                        <input type="email"
                               class="form-control"
                               name="email"
                               required
                               value="<?= $auth->check() ? clean($auth->user()['email']) : '' ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="tel"
                               class="form-control"
                               name="phone"
                               placeholder="5XX XXX XX XX"
                               value="<?= $auth->check() ? clean($auth->user()['phone'] ?? '') : '' ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Yaş</label>
                        <input type="number"
                               class="form-control"
                               name="age"
                               min="1"
                               max="120">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Cinsiyet</label>
                        <select class="form-select" name="gender">
                            <option value="">Seçiniz</option>
                            <option value="male">Erkek</option>
                            <option value="female">Kadın</option>
                            <option value="other">Diğer</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Boy (cm)</label>
                        <input type="number"
                               class="form-control"
                               name="height"
                               step="0.01"
                               min="50"
                               max="250">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Kilo (kg)</label>
                        <input type="number"
                               class="form-control"
                               name="weight"
                               step="0.01"
                               min="20"
                               max="300">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Sağlık Durumunuz (Kronik hastalıklar, alerjiler vb.)</label>
                    <textarea class="form-control"
                              name="health_conditions"
                              rows="3"
                              placeholder="Örn: Diyabet, hipertansiyon, kolesterol yüksekliği..."></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kullandığınız İlaçlar</label>
                    <textarea class="form-control"
                              name="medications"
                              rows="2"
                              placeholder="Kullandığınız ilaçları belirtiniz..."></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">
                        Aciliyet Seviyesi<span class="required">*</span>
                    </label>
                    <div class="urgency-selector">
                        <div class="urgency-option">
                            <input type="radio" name="urgency_level" value="low" id="urgency_low">
                            <label class="urgency-label" for="urgency_low">
                                <i class="fas fa-info-circle d-block mb-2" style="font-size: 1.5rem; color: #3b82f6;"></i>
                                Düşük
                            </label>
                        </div>
                        <div class="urgency-option">
                            <input type="radio" name="urgency_level" value="medium" id="urgency_medium" checked>
                            <label class="urgency-label" for="urgency_medium">
                                <i class="fas fa-exclamation-circle d-block mb-2" style="font-size: 1.5rem; color: #f59e0b;"></i>
                                Orta
                            </label>
                        </div>
                        <div class="urgency-option">
                            <input type="radio" name="urgency_level" value="high" id="urgency_high">
                            <label class="urgency-label" for="urgency_high">
                                <i class="fas fa-exclamation-triangle d-block mb-2" style="font-size: 1.5rem; color: #f97316;"></i>
                                Yüksek
                            </label>
                        </div>
                        <div class="urgency-option">
                            <input type="radio" name="urgency_level" value="critical" id="urgency_critical">
                            <label class="urgency-label" for="urgency_critical">
                                <i class="fas fa-ambulance d-block mb-2" style="font-size: 1.5rem; color: #ef4444;"></i>
                                Kritik
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">
                        Talebiniz<span class="required">*</span>
                    </label>
                    <textarea class="form-control"
                              name="message"
                              rows="5"
                              required
                              placeholder="Lütfen acil destek ihtiyacınızı detaylı bir şekilde açıklayın..."></textarea>
                    <small class="text-muted">Minimum 20 karakter</small>
                </div>

                <button type="submit" class="btn-submit-request">
                    <i class="fas fa-paper-plane me-2"></i>
                    Acil Destek Talebi Gönder
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('emergencyRequestForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Gönderiliyor...';

    const formData = new FormData(e.target);

    try {
        const response = await fetch('/api/emergency-request.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        const alertContainer = document.getElementById('alert-container');

        if (result.success) {
            alertContainer.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Başarılı!</strong> Acil destek talebiniz alındı. Admin ekibimiz en kısa sürede size dönüş yapacaktır.
                    <br><small>Talep Numarası: #${result.request_id}</small>
                </div>
            `;
            e.target.reset();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            alertContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Hata!</strong> ${result.message || 'Talep gönderilemedi. Lütfen tekrar deneyin.'}
                </div>
            `;
        }
    } catch (error) {
        document.getElementById('alert-container').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Hata!</strong> Bir sorun oluştu. Lütfen daha sonra tekrar deneyin.
            </div>
        `;
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});
</script>

<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
