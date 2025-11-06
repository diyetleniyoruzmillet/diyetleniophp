<?php
/**
 * FAQ - Sıkça Sorulan Sorular
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Sıkça Sorulan Sorular';
$metaDescription = 'Diyetlenio hakkında sıkça sorulan sorular ve cevapları';
include __DIR__ . '/../includes/partials/header.php';
?>

<style>
    .hero {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
        color: white;
        padding: 100px 0 80px;
        text-align: center;
    }

    .hero h1 {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 1rem;
    }

    .faq-section {
        padding: 60px 0;
    }

    .faq-item {
        background: white;
        border-radius: 16px;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        overflow: hidden;
        border: 2px solid transparent;
        transition: all 0.3s;
    }

    .faq-item:hover {
        border-color: #10b981;
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }

    .faq-question {
        padding: 1.5rem 2rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        color: #0f172a;
        user-select: none;
    }

    .faq-question i {
        color: #10b981;
        transition: transform 0.3s;
    }

    .faq-question.active i {
        transform: rotate(180deg);
    }

    .faq-answer {
        padding: 0 2rem;
        max-height: 0;
        overflow: hidden;
        transition: all 0.3s;
        color: #64748b;
        line-height: 1.7;
    }

    .faq-answer.show {
        padding: 0 2rem 1.5rem;
        max-height: 500px;
    }
</style>

<div class="hero">
    <div class="container">
        <h1><i class="fas fa-question-circle me-3"></i>Sıkça Sorulan Sorular</h1>
        <p class="lead">Merak ettikleriniz burada!</p>
    </div>
</div>

<div class="faq-section">
    <div class="container" style="max-width: 900px;">
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span>Diyetlenio nedir?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Diyetlenio, online diyetisyen danışmanlığı platformudur. Uzman diyetisyenlerle video görüşme yaparak
                kişiselleştirilmiş beslenme planları alabilir, hedeflerinize ulaşabilirsiniz.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span>Video görüşmeler nasıl çalışıyor?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Randevunuzdan 30 dakika önce "Görüşmeye Katıl" butonu aktif olur. Butona tıklayarak güvenli video odasına
                girebilir ve diyetisyeninizle yüz yüze görüşebilirsiniz. Herhangi bir uygulama indirmenize gerek yoktur.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span>Ücretler nasıl belirleniyor?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Her diyetisyen kendi ücretlerini belirler. Diyetisyen profillerinde seans ücretlerini görebilirsiniz.
                Ortalama bir seans ücreti 200-500 TL arasındadır.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span>Randevu nasıl alırım?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Diyetisyenler sayfasından size uygun bir diyetisyen seçin, profilini inceleyin ve müsait saatlerden
                randevu oluşturun. Ödemenizi yaptıktan sonra randevunuz onaylanacaktır.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span>Randevumu iptal edebilir miyim?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Evet. Randevu saatinden en az 24 saat önce iptal ederseniz ücretiniz iade edilir.
                24 saaten daha yakın iptal işlemlerinde iade yapılmaz.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span>Diyetisyen olarak nasıl katılabilirim?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                "Diyetisyen Ol" butonuna tıklayarak başvuru yapabilirsiniz. Diploma ve lisans belgelerinizi yükledikten sonra
                başvurunuz incelenecek ve onaylanacaktır. Onay süreci 2-3 iş günü sürmektedir.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span>Verilerim güvende mi?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Evet. Tüm verileriniz şifrelenmiş olarak saklanır ve KVKK kurallarına uygun şekilde işlenir.
                Bilgileriniz hiçbir şekilde üçüncü şahıslarla paylaşılmaz.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span>Hangi ödeme yöntemlerini kabul ediyorsunuz?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Kredi kartı, banka kartı ve havale/EFT ile ödeme yapabilirsiniz. Tüm ödemeler güvenli ödeme altyapısı ile işlenir.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span>Başka sorularım var, kimle iletişime geçebilirim?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <a href="/contact.php">İletişim</a> sayfasından bizle iletişime geçebilirsiniz.
                Email: info@diyetlenio.com | Telefon: 0850 123 45 67
            </div>
        </div>
    </div>
</div>

<script>
    function toggleFAQ(element) {
        const answer = element.nextElementSibling;
        const isOpen = answer.classList.contains('show');

        // Close all
        document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('show'));
        document.querySelectorAll('.faq-question').forEach(q => q.classList.remove('active'));

        // Open clicked if it was closed
        if (!isOpen) {
            answer.classList.add('show');
            element.classList.add('active');
        }
    }
</script>

<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
