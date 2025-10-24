<?php
/**
 * Diyetlenio - Sık Sorulan Sorular
 */

require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Sık Sorulan Sorular';
$bodyClass = 'faq';
?>
<?php include __DIR__ . '/../includes/partials/header.php'; ?>
    

    <section class="hero">
        <div class="container">
            <h1>Sık Sorulan Sorular</h1>
            <p>Merak ettiklerinizin cevaplarını burada bulabilirsiniz</p>
        </div>
    </section>

    <section class="faq-section">
        <div class="container">
            <div class="category-tabs">
                <ul class="nav nav-pills justify-content-center" id="faqTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#general">Genel</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#appointments">Randevular</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#payments">Ödemeler</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#technical">Teknik</button>
                    </li>
                </ul>
            </div>

            <div class="tab-content">
                <!-- Genel Sorular -->
                <div class="tab-pane fade show active" id="general">
                    <div class="accordion" id="generalAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#q1">
                                    <i class="fas fa-question-circle me-3"></i>Diyetlenio nedir?
                                </button>
                            </h2>
                            <div id="q1" class="accordion-collapse collapse show" data-bs-parent="#generalAccordion">
                                <div class="accordion-body">
                                    Diyetlenio, lisanslı diyetisyenler ile danışanları online olarak buluşturan bir platformdur. Video görüşme, mesajlaşma ve diyet takibi gibi hizmetler sunmaktayız.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q2">
                                    <i class="fas fa-question-circle me-3"></i>Diyetisyenler lisanslı mı?
                                </button>
                            </h2>
                            <div id="q2" class="accordion-collapse collapse" data-bs-parent="#generalAccordion">
                                <div class="accordion-body">
                                    Evet, platformumuzdaki tüm diyetisyenler geçerli diploma ve uzmanlık belgelerine sahiptir. Her diyetisyen başvurusu titizlikle incelenir.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q3">
                                    <i class="fas fa-question-circle me-3"></i>Nasıl kayıt olurum?
                                </button>
                            </h2>
                            <div id="q3" class="accordion-collapse collapse" data-bs-parent="#generalAccordion">
                                <div class="accordion-body">
                                    Ana sayfadaki "Kayıt Ol" butonuna tıklayarak email adresiniz ve şifreniz ile hızlıca kayıt olabilirsiniz. Danışan veya diyetisyen olarak kayıt olabilirsiniz.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q4">
                                    <i class="fas fa-question-circle me-3"></i>Üyelik ücreti var mı?
                                </button>
                            </h2>
                            <div id="q4" class="accordion-collapse collapse" data-bs-parent="#generalAccordion">
                                <div class="accordion-body">
                                    Hayır, platform kullanımı ücretsizdir. Sadece diyetisyen randevuları için ödeme yaparsınız.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Randevu Soruları -->
                <div class="tab-pane fade" id="appointments">
                    <div class="accordion" id="appointmentsAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#a1">
                                    <i class="fas fa-question-circle me-3"></i>Nasıl randevu alırım?
                                </button>
                            </h2>
                            <div id="a1" class="accordion-collapse collapse show" data-bs-parent="#appointmentsAccordion">
                                <div class="accordion-body">
                                    Diyetisyenler sayfasından size uygun diyetisyeni seçin, müsait zaman dilimlerini görüntüleyin ve randevu oluşturun. Ödeme sonrası randevunuz onaylanır.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a2">
                                    <i class="fas fa-question-circle me-3"></i>Randevumu iptal edebilir miyim?
                                </button>
                            </h2>
                            <div id="a2" class="accordion-collapse collapse" data-bs-parent="#appointmentsAccordion">
                                <div class="accordion-body">
                                    Evet, randevudan 24 saat öncesine kadar ücretsiz iptal edebilirsiniz. 24 saatten sonraki iptallerde %50 kesinti uygulanır.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a3">
                                    <i class="fas fa-question-circle me-3"></i>Online görüşme nasıl yapılır?
                                </button>
                            </h2>
                            <div id="a3" class="accordion-collapse collapse" data-bs-parent="#appointmentsAccordion">
                                <div class="accordion-body">
                                    Randevu saatinde "Randevularım" sayfasından "Görüşmeye Başla" butonuna tıklayın. Tarayıcınızdan kamera ve mikrofon izni verin.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a4">
                                    <i class="fas fa-question-circle me-3"></i>Randevu süresi ne kadar?
                                </button>
                            </h2>
                            <div id="a4" class="accordion-collapse collapse" data-bs-parent="#appointmentsAccordion">
                                <div class="accordion-body">
                                    Standart randevu süresi 30 dakikadır. Bazı diyetisyenler 45 veya 60 dakikalık seçenekler de sunabilir.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ödeme Soruları -->
                <div class="tab-pane fade" id="payments">
                    <div class="accordion" id="paymentsAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#p1">
                                    <i class="fas fa-question-circle me-3"></i>Hangi ödeme yöntemlerini kabul ediyorsunuz?
                                </button>
                            </h2>
                            <div id="p1" class="accordion-collapse collapse show" data-bs-parent="#paymentsAccordion">
                                <div class="accordion-body">
                                    Kredi kartı, banka kartı ve havale/EFT ile ödeme kabul edilmektedir. Ödemeleriniz güvenli SSL şifrelemesi ile korunmaktadır.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#p2">
                                    <i class="fas fa-question-circle me-3"></i>İade nasıl alırım?
                                </button>
                            </h2>
                            <div id="p2" class="accordion-collapse collapse" data-bs-parent="#paymentsAccordion">
                                <div class="accordion-body">
                                    İptal şartlarına uygun iptellerde iade, 5-10 iş günü içinde ödeme yaptığınız kartınıza yapılır.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#p3">
                                    <i class="fas fa-question-circle me-3"></i>Fatura alabilir miyim?
                                </button>
                            </h2>
                            <div id="p3" class="accordion-collapse collapse" data-bs-parent="#paymentsAccordion">
                                <div class="accordion-body">
                                    Evet, her ödemeniz için otomatik fatura düzenlenir. Faturalarınızı "Ödemeler" sayfasından görüntüleyip indirebilirsiniz.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Teknik Sorular -->
                <div class="tab-pane fade" id="technical">
                    <div class="accordion" id="technicalAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#t1">
                                    <i class="fas fa-question-circle me-3"></i>Hangi tarayıcıları destekliyorsunuz?
                                </button>
                            </h2>
                            <div id="t1" class="accordion-collapse collapse show" data-bs-parent="#technicalAccordion">
                                <div class="accordion-body">
                                    Chrome, Firefox, Safari ve Edge tarayıcılarının güncel versiyonlarını destekliyoruz. En iyi deneyim için Chrome öneriyoruz.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#t2">
                                    <i class="fas fa-question-circle me-3"></i>Mobil uygulama var mı?
                                </button>
                            </h2>
                            <div id="t2" class="accordion-collapse collapse" data-bs-parent="#technicalAccordion">
                                <div class="accordion-body">
                                    Şu anda web platformumuz mobil uyumludur. iOS ve Android uygulamalarımız yakında yayınlanacaktır.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#t3">
                                    <i class="fas fa-question-circle me-3"></i>Video görüşmede sorun yaşarsam ne yapmalıyım?
                                </button>
                            </h2>
                            <div id="t3" class="accordion-collapse collapse" data-bs-parent="#technicalAccordion">
                                <div class="accordion-body">
                                    Kamera ve mikrofon izinlerini kontrol edin, tarayıcınızı güncelleyin ve internet bağlantınızı kontrol edin. Sorun devam ederse destek@diyetlenio.com adresine yazın.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <p class="text-muted mb-3">Sorunuza cevap bulamadınız mı?</p>
                <a href="/contact.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-envelope me-2"></i>Bize Ulaşın
                </a>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Diyetlenio. Tüm hakları saklıdır.</p>
            <div class="mt-3">
                <a href="/about.php" class="me-3">Hakkımızda</a>
                <a href="/privacy-policy.php" class="me-3">Gizlilik Politikası</a>
                <a href="/terms.php">Kullanım Şartları</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
