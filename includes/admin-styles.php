<style>
    body {
        background: #f8f9fa;
    }

    .sidebar {
        min-height: 100vh;
        background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        box-shadow: 4px 0 30px rgba(0,0,0,0.15);
        position: relative;
        overflow: hidden;
    }

    .sidebar::before {
        content: '';
        position: absolute;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
        border-radius: 50%;
        top: -100px;
        right: -100px;
        animation: pulse 4s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.8; }
    }

    .sidebar-brand {
        font-size: 1.8rem;
        font-weight: 800;
        color: white;
        margin-bottom: 0.5rem;
        position: relative;
        z-index: 1;
    }

    .sidebar-subtitle {
        font-size: 0.85rem;
        color: rgba(255,255,255,0.7);
        font-weight: 300;
        position: relative;
        z-index: 1;
    }

    .sidebar .nav-link {
        color: rgba(255,255,255,0.85);
        padding: 14px 20px;
        margin: 6px 0;
        border-radius: 12px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 12px;
        position: relative;
        z-index: 1;
    }

    .sidebar .nav-link:hover {
        color: #fff;
        background: rgba(255,255,255,0.15);
        transform: translateX(5px);
    }

    .sidebar .nav-link.active {
        color: #fff;
        background: rgba(255,255,255,0.25);
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        font-weight: 600;
    }

    .sidebar .nav-link i {
        font-size: 1.1rem;
        width: 20px;
        text-align: center;
    }

    .sidebar .nav-link .badge {
        margin-left: auto;
    }

    .content-wrapper {
        padding: 30px;
    }

    .page-header {
        background: white;
        padding: 25px 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .page-header h2 {
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .stat-card {
        border-left: 4px solid;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-card-1 { border-left-color: #667eea; }
    .stat-card-2 { border-left-color: #56ab2f; }
    .stat-card-3 { border-left-color: #f093fb; }
    .stat-card-4 { border-left-color: #ffd89b; }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }

    .icon-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .icon-success {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
    }

    .icon-info {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .icon-warning {
        background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%);
    }

    .table {
        margin-bottom: 0;
    }

    .table thead th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        color: #6c757d;
        border-bottom: 2px solid #dee2e6;
        padding: 15px;
    }

    .table tbody tr {
        transition: all 0.2s ease;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
        transform: scale(1.01);
    }

    .table tbody td {
        padding: 15px;
        vertical-align: middle;
    }

    .btn {
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .badge {
        padding: 6px 12px;
        border-radius: 8px;
        font-weight: 500;
    }
</style>
