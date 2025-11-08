<?php
/**
 * 404 Error Page
 * Sayfa bulunamadı
 */

require_once __DIR__ . '/../includes/bootstrap.php';

http_response_code(404);
$pageTitle = "Sayfa Bulunamadı - 404";

require_once __DIR__ . '/../includes/partials/header.php';
?>

<style>
    .error-page {
        min-height: calc(100vh - 200px);
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position: relative;
        overflow: hidden;
    }

    .error-page::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.3;
    }

    .error-content {
        text-align: center;
        color: white;
        position: relative;
        z-index: 1;
        max-width: 600px;
        padding: 2rem;
    }

    .error-code {
        font-size: 150px;
        font-weight: 900;
        line-height: 1;
        margin: 0;
        text-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-20px);
        }
    }

    .error-title {
        font-size: 2rem;
        font-weight: 700;
        margin: 1rem 0;
    }

    .error-description {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 2rem;
    }

    .error-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .error-btn {
        padding: 12px 30px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .error-btn-primary {
        background: white;
        color: #667eea;
    }

    .error-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        color: #667eea;
    }

    .error-btn-secondary {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid white;
    }

    .error-btn-secondary:hover {
        background: white;
        color: #667eea;
        transform: translateY(-2px);
    }

    .search-box {
        margin-top: 2rem;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }

    .search-box input {
        border-radius: 50px;
        padding: 12px 20px;
        border: none;
        width: 100%;
    }

    .popular-links {
        margin-top: 3rem;
        padding-top: 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
    }

    .popular-links h3 {
        font-size: 1.2rem;
        margin-bottom: 1rem;
        opacity: 0.9;
    }

    .popular-links ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        gap: 1.5rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .popular-links a {
        color: white;
        text-decoration: none;
        opacity: 0.8;
        transition: opacity 0.3s ease;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .popular-links a:hover {
        opacity: 1;
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .error-code {
            font-size: 100px;
        }

        .error-title {
            font-size: 1.5rem;
        }

        .error-description {
            font-size: 1rem;
        }

        .error-actions {
            flex-direction: column;
        }

        .error-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="error-page">
    <div class="error-content">
        <h1 class="error-code">404</h1>
        <h2 class="error-title">Aradığınız Sayfa Bulunamadı</h2>
        <p class="error-description">
            Üzgünüz, aradığınız sayfa taşınmış, silinmiş veya hiç var olmamış olabilir.
            Aşağıdaki bağlantılardan devam edebilirsiniz.
        </p>

        <div class="error-actions">
            <a href="/" class="error-btn error-btn-primary">
                <i class="bi bi-house-door"></i>
                Ana Sayfaya Dön
            </a>
            <a href="javascript:history.back()" class="error-btn error-btn-secondary">
                <i class="bi bi-arrow-left"></i>
                Geri Dön
            </a>
        </div>

        <div class="search-box">
            <form action="/search.php" method="GET">
                <input type="text" name="q" class="form-control" placeholder="Site içinde ara..." required>
            </form>
        </div>

        <div class="popular-links">
            <h3>Popüler Sayfalar</h3>
            <ul>
                <li>
                    <a href="/dietitians.php">
                        <i class="bi bi-people"></i>
                        Diyetisyenler
                    </a>
                </li>
                <li>
                    <a href="/recipes.php">
                        <i class="bi bi-book"></i>
                        Tarifler
                    </a>
                </li>
                <li>
                    <a href="/blog.php">
                        <i class="bi bi-newspaper"></i>
                        Blog
                    </a>
                </li>
                <li>
                    <a href="/about.php">
                        <i class="bi bi-info-circle"></i>
                        Hakkımızda
                    </a>
                </li>
                <li>
                    <a href="/contact.php">
                        <i class="bi bi-envelope"></i>
                        İletişim
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
// Scroll to top on load
window.scrollTo(0, 0);

// Track 404 errors (optional analytics)
if (typeof gtag !== 'undefined') {
    gtag('event', 'page_not_found', {
        'page_path': window.location.pathname
    });
}
</script>

<?php require_once __DIR__ . '/../includes/partials/footer.php'; ?>
