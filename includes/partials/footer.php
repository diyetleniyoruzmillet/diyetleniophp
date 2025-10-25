<?php
/**
 * Ortak Footer Partial
 * Değişkenler (opsiyonel):
 *  - $showFooter : Basit bir footer alanı gösterilsin mi (default: false)
 */

$showFooter = array_key_exists('showFooter', get_defined_vars()) ? (bool)$showFooter : false;
?>

<?php if ($showFooter): ?>
    <footer class="mt-5 py-4 text-center text-muted border-top">
        <div class="container">
            © <?= date('Y') ?> Diyetlenio. Tüm hakları saklıdır.
        </div>
    </footer>
<?php endif; ?>

<!-- Floating Emergency Dietitian Button -->
<a href="/acil-diyetisyen.php" class="emergency-dietitian-btn" id="emergencyBtn" title="Acil Diyetisyen Desteği">
    <div class="emergency-icon">
        <i class="fas fa-heartbeat"></i>
    </div>
    <span class="emergency-text">Acil Diyetisyen</span>
</a>

<style>
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

    /* Mobile responsive */
    @media (max-width: 768px) {
        .emergency-dietitian-btn {
            bottom: 20px;
            right: 20px;
            padding: 14px 20px;
            font-size: 0.9rem;
            max-width: 60px;
        }

        .emergency-icon {
            width: 32px;
            height: 32px;
            font-size: 1.1rem;
        }

        .emergency-dietitian-btn:hover {
            max-width: 220px;
        }
    }

    /* Hide on print */
    @media print {
        .emergency-dietitian-btn {
            display: none !important;
        }
    }
</style>

<script>
    // Add click tracking for emergency button
    document.getElementById('emergencyBtn')?.addEventListener('click', function(e) {
        // Optional: Add analytics tracking here
        console.log('Emergency Dietitian button clicked');
    });

    // Add subtle bounce animation on page load
    window.addEventListener('load', function() {
        const btn = document.getElementById('emergencyBtn');
        if (btn) {
            setTimeout(() => {
                btn.style.animation = 'pulseGlow 2s ease-in-out infinite, fadeInUp 0.6s ease-out';
            }, 500);
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

