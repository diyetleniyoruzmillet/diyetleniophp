<?php
/**
 * Modern Footer Component
 */
?>

<!-- Modern Footer -->
<footer class="modern-footer">
    <div class="footer-wave">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z"></path>
        </svg>
    </div>

    <div class="container">
        <div class="row g-4">
            <!-- Hakkımızda -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-heartbeat"></i>
                        <h3>Diyetlenio</h3>
                    </div>
                    <p class="footer-description">
                        Türkiye'nin en güvenilir online diyetisyen platformu.
                        Uzman diyetisyenlerimizle sağlıklı yaşam yolculuğunuza başlayın.
                    </p>
                    <div class="footer-stats">
                        <div class="stat-item">
                            <i class="fas fa-users"></i>
                            <span><strong>10,000+</strong> Mutlu Kullanıcı</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-user-md"></i>
                            <span><strong>100+</strong> Uzman Diyetisyen</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hızlı Linkler -->
            <div class="col-lg-2 col-md-6">
                <div class="footer-section">
                    <h4 class="footer-title">Keşfet</h4>
                    <ul class="footer-links">
                        <li><a href="/"><i class="fas fa-home"></i> Ana Sayfa</a></li>
                        <li><a href="/dietitians.php"><i class="fas fa-user-md"></i> Diyetisyenler</a></li>
                        <li><a href="/about.php"><i class="fas fa-info-circle"></i> Hakkımızda</a></li>
                        <li><a href="/blog.php"><i class="fas fa-blog"></i> Blog</a></li>
                        <li><a href="/faq.php"><i class="fas fa-question-circle"></i> SSS</a></li>
                    </ul>
                </div>
            </div>

            <!-- Hizmetler -->
            <div class="col-lg-2 col-md-6">
                <div class="footer-section">
                    <h4 class="footer-title">Hizmetler</h4>
                    <ul class="footer-links">
                        <li><a href="/register-client.php"><i class="fas fa-user-plus"></i> Üye Ol</a></li>
                        <li><a href="/register-dietitian.php"><i class="fas fa-stethoscope"></i> Diyetisyen Ol</a></li>
                        <li><a href="/acil-diyetisyen.php"><i class="fas fa-heartbeat"></i> Acil Destek</a></li>
                        <li><a href="/recipes.php"><i class="fas fa-utensils"></i> Tarifler</a></li>
                        <li><a href="/contact.php"><i class="fas fa-envelope"></i> İletişim</a></li>
                    </ul>
                </div>
            </div>

            <!-- İletişim -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-section">
                    <h4 class="footer-title">İletişim</h4>
                    <ul class="footer-contact">
                        <li>
                            <i class="fas fa-envelope"></i>
                            <div>
                                <strong>Email</strong>
                                <a href="mailto:info@diyetlenio.com">info@diyetlenio.com</a>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <div>
                                <strong>Telefon</strong>
                                <a href="tel:+908501234567">0850 123 45 67</a>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong>Adres</strong>
                                <span>İstanbul, Türkiye</span>
                            </div>
                        </li>
                    </ul>

                    <!-- Sosyal Medya -->
                    <div class="social-links">
                        <a href="#" class="social-link" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" title="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="social-link" title="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <p class="mb-0">
                        © <?= date('Y') ?> <strong>Diyetlenio</strong>. Tüm hakları saklıdır.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="footer-legal">
                        <li><a href="/page/kullanim-sartlari">Kullanım Şartları</a></li>
                        <li><a href="/page/gizlilik-politikasi">Gizlilik Politikası</a></li>
                        <li><a href="/page/kvkk">KVKK</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Floating Emergency Dietitian Button -->
<a href="/acil-diyetisyen.php" class="emergency-dietitian-btn" id="emergencyBtn" title="Acil Diyetisyen Desteği">
    <div class="emergency-icon">
        <i class="fas fa-heartbeat"></i>
    </div>
    <span class="emergency-text">Acil Diyetisyen</span>
</a>

<style>
    /* Modern Footer Styles */
    .modern-footer {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: #e2e8f0;
        position: relative;
        margin-top: 80px;
        padding: 60px 0 0;
        overflow: hidden;
    }

    .footer-wave {
        position: absolute;
        top: -80px;
        left: 0;
        width: 100%;
        overflow: hidden;
        line-height: 0;
    }

    .footer-wave svg {
        position: relative;
        display: block;
        width: calc(100% + 1.3px);
        height: 80px;
    }

    .footer-wave path {
        fill: #1e293b;
    }

    .footer-section {
        margin-bottom: 30px;
    }

    .footer-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .footer-logo i {
        font-size: 2rem;
        color: #10b981;
        animation: pulse 2s ease-in-out infinite;
    }

    .footer-logo h3 {
        font-size: 1.5rem;
        font-weight: 800;
        color: white;
        margin: 0;
        background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .footer-description {
        color: #cbd5e1;
        line-height: 1.7;
        margin-bottom: 20px;
        font-size: 0.95rem;
    }

    .footer-stats {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #cbd5e1;
        font-size: 0.9rem;
    }

    .stat-item i {
        color: #10b981;
        font-size: 1.1rem;
    }

    .footer-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: white;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #10b981;
        display: inline-block;
    }

    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-links li {
        margin-bottom: 12px;
    }

    .footer-links a {
        color: #cbd5e1;
        text-decoration: none;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
    }

    .footer-links a i {
        color: #10b981;
        font-size: 0.9rem;
        width: 16px;
    }

    .footer-links a:hover {
        color: #10b981;
        transform: translateX(5px);
    }

    .footer-contact {
        list-style: none;
        padding: 0;
        margin: 0 0 20px 0;
    }

    .footer-contact li {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }

    .footer-contact i {
        color: #10b981;
        font-size: 1.2rem;
        width: 20px;
        flex-shrink: 0;
        margin-top: 3px;
    }

    .footer-contact div {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .footer-contact strong {
        color: white;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .footer-contact a,
    .footer-contact span {
        color: #cbd5e1;
        text-decoration: none;
        transition: color 0.3s;
        font-size: 0.95rem;
    }

    .footer-contact a:hover {
        color: #10b981;
    }

    .social-links {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .social-link {
        width: 42px;
        height: 42px;
        background: rgba(16, 185, 129, 0.1);
        border: 2px solid rgba(16, 185, 129, 0.3);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #10b981;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 1rem;
    }

    .social-link:hover {
        background: #10b981;
        color: white;
        border-color: #10b981;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    }

    .footer-bottom {
        margin-top: 50px;
        padding: 30px 0;
        border-top: 1px solid rgba(226, 232, 240, 0.1);
    }

    .footer-bottom p {
        color: #cbd5e1;
        font-size: 0.9rem;
    }

    .footer-legal {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        justify-content: center;
    }

    @media (min-width: 768px) {
        .footer-legal {
            justify-content: flex-end;
        }
    }

    .footer-legal li::after {
        content: "•";
        margin-left: 20px;
        color: #475569;
    }

    .footer-legal li:last-child::after {
        display: none;
    }

    .footer-legal a {
        color: #cbd5e1;
        text-decoration: none;
        transition: color 0.3s;
        font-size: 0.9rem;
    }

    .footer-legal a:hover {
        color: #10b981;
    }

    /* Emergency Button */
    .emergency-dietitian-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: linear-gradient(135deg, #dc2626 0%, #f97316 100%);
        color: white;
        padding: 16px 24px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 700;
        font-size: 1rem;
        box-shadow: 0 8px 30px rgba(220, 38, 38, 0.5);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        animation: pulseGlow 2s ease-in-out infinite;
        overflow: visible;
        white-space: nowrap;
    }

    .emergency-dietitian-btn::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.6s;
    }

    .emergency-dietitian-btn:hover::before {
        left: 100%;
    }

    .emergency-dietitian-btn:hover {
        color: white;
        transform: translateY(-5px) scale(1.05);
        box-shadow: 0 12px 45px rgba(220, 38, 38, 0.7);
    }

    .emergency-icon {
        width: 38px;
        height: 38px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
        animation: pulse 2s ease-in-out infinite;
    }

    .emergency-text {
        opacity: 1;
        white-space: nowrap;
        font-weight: 700;
    }

    @keyframes pulseGlow {
        0%, 100% {
            box-shadow: 0 8px 30px rgba(220, 38, 38, 0.5);
        }
        50% {
            box-shadow: 0 8px 40px rgba(220, 38, 38, 0.8), 0 0 0 10px rgba(220, 38, 38, 0.1);
        }
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
        }
    }

    /* Responsive */
    @media (max-width: 991px) {
        .modern-footer {
            padding: 50px 0 0;
        }

        .footer-title {
            font-size: 1rem;
        }
    }

    @media (max-width: 768px) {
        .modern-footer {
            margin-top: 60px;
            padding: 40px 0 0;
        }

        .footer-wave {
            top: -60px;
        }

        .footer-wave svg {
            height: 60px;
        }

        .footer-section {
            text-align: center;
        }

        .footer-logo {
            justify-content: center;
        }

        .footer-title {
            width: 100%;
            text-align: center;
        }

        .footer-links a,
        .footer-contact li {
            justify-content: center;
        }

        .social-links {
            justify-content: center;
        }

        .footer-bottom {
            margin-top: 30px;
            padding: 20px 0;
        }

        .emergency-dietitian-btn {
            bottom: 20px;
            right: 20px;
            padding: 14px 20px;
            font-size: 0.9rem;
        }

        .emergency-icon {
            width: 32px;
            height: 32px;
            font-size: 1.1rem;
        }
    }

    @media (max-width: 576px) {
        .footer-legal {
            flex-direction: column;
            gap: 10px;
        }

        .footer-legal li::after {
            display: none;
        }
    }

    /* Hide on print */
    @media print {
        .modern-footer,
        .emergency-dietitian-btn {
            display: none !important;
        }
    }
</style>

<script>
    // Emergency button interactions
    document.getElementById('emergencyBtn')?.addEventListener('click', function(e) {
        console.log('Emergency Dietitian button clicked');
    });

    // Smooth scroll to top on footer logo click
    document.querySelector('.footer-logo')?.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
