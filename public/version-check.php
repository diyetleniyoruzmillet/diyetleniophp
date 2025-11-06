<?php
/**
 * Deployment Version Checker
 * Use this to verify which version is deployed on Railway
 */

header('Content-Type: text/html; charset=utf-8');

$gitCommit = trim(shell_exec('git rev-parse --short HEAD 2>/dev/null') ?? 'unknown');
$gitBranch = trim(shell_exec('git branch --show-current 2>/dev/null') ?? 'unknown');
$lastCommitDate = trim(shell_exec('git log -1 --format=%cd 2>/dev/null') ?? 'unknown');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Diyetlenio - Version Check</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #0f172a;
            color: #10b981;
            padding: 40px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #1e293b;
            padding: 30px;
            border-radius: 12px;
            border: 2px solid #10b981;
        }
        h1 {
            color: #10b981;
            border-bottom: 2px solid #10b981;
            padding-bottom: 10px;
        }
        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #334155;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            width: 200px;
            color: #60a5fa;
        }
        .value {
            color: #fbbf24;
        }
        .success {
            color: #10b981;
            font-weight: bold;
        }
        .error {
            color: #ef4444;
            font-weight: bold;
        }
        .footer-test {
            margin-top: 30px;
            padding: 20px;
            background: #334155;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Deployment Version Check</h1>

        <div class="info-row">
            <div class="label">Current Time:</div>
            <div class="value"><?= date('Y-m-d H:i:s') ?> UTC</div>
        </div>

        <div class="info-row">
            <div class="label">Git Commit:</div>
            <div class="value"><?= htmlspecialchars($gitCommit) ?></div>
        </div>

        <div class="info-row">
            <div class="label">Git Branch:</div>
            <div class="value"><?= htmlspecialchars($gitBranch) ?></div>
        </div>

        <div class="info-row">
            <div class="label">Last Commit:</div>
            <div class="value"><?= htmlspecialchars($lastCommitDate) ?></div>
        </div>

        <div class="info-row">
            <div class="label">PHP Version:</div>
            <div class="value"><?= PHP_VERSION ?></div>
        </div>

        <div class="info-row">
            <div class="label">Server:</div>
            <div class="value"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></div>
        </div>

        <div class="info-row">
            <div class="label">Expected Commit:</div>
            <div class="value">d109519</div>
        </div>

        <div class="info-row">
            <div class="label">Deployment Status:</div>
            <div class="value <?= ($gitCommit === 'd109519' || strpos($gitCommit, 'd109519') === 0) ? 'success' : 'error' ?>">
                <?= ($gitCommit === 'd109519' || strpos($gitCommit, 'd109519') === 0) ? '✅ UP TO DATE' : '❌ OLD VERSION - CACHE ISSUE!' ?>
            </div>
        </div>

        <div class="footer-test">
            <h3>Footer File Check</h3>
            <div class="info-row">
                <div class="label">Footer Path:</div>
                <div class="value"><?= file_exists(__DIR__ . '/../includes/partials/footer.php') ? '✅ EXISTS' : '❌ MISSING' ?></div>
            </div>
            <div class="info-row">
                <div class="label">Footer Size:</div>
                <div class="value"><?= file_exists(__DIR__ . '/../includes/partials/footer.php') ? filesize(__DIR__ . '/../includes/partials/footer.php') . ' bytes' : 'N/A' ?></div>
            </div>
            <div class="info-row">
                <div class="label">Footer Modified:</div>
                <div class="value"><?= file_exists(__DIR__ . '/../includes/partials/footer.php') ? date('Y-m-d H:i:s', filemtime(__DIR__ . '/../includes/partials/footer.php')) : 'N/A' ?></div>
            </div>
        </div>

        <div style="margin-top: 30px; padding: 20px; background: #1e40af; border-radius: 8px;">
            <h3 style="color: #fff;">🔧 If you see "OLD VERSION":</h3>
            <ol style="color: #ddd;">
                <li>Railway is using cached build</li>
                <li>Go to Railway Dashboard</li>
                <li>Settings → Clear Build Cache</li>
                <li>Manually trigger Redeploy</li>
                <li>Wait 2-3 minutes</li>
                <li>Refresh this page</li>
            </ol>
        </div>
    </div>
</body>
</html>
