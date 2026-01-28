<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sidebar Toggle Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Mobile Sidebar Styles */
        @media (max-width: 991.98px) {
            /* Hide sidebar by default on mobile */
            .left-sidebar {
                position: fixed !important;
                top: 0;
                left: -280px !important;
                width: 280px !important;
                height: 100vh !important;
                z-index: 1040 !important;
                background: white !important;
                transition: left 0.3s ease !important;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1) !important;
                overflow-y: auto !important;
            }
            
            /* Show sidebar when active */
            .left-sidebar.show-sidebar {
                left: 0 !important;
            }
            
            /* Overlay backdrop */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1035;
                transition: opacity 0.3s ease;
            }
            
            .sidebar-overlay.show {
                display: block !important;
            }
            
            body {
                padding-bottom: 70px;
            }
        }
        
        /* Desktop */
        @media (min-width: 992px) {
            .left-sidebar {
                position: static !important;
                left: 0 !important;
                width: auto !important;
                height: auto !important;
                box-shadow: none !important;
            }
        }
        
        .topbar {
            background: #f8f9fa;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .hamburger {
            cursor: pointer;
            font-size: 24px;
            background: none;
            border: none;
            padding: 5px;
        }
        
        .left-sidebar {
            background: #fff;
            border-right: 1px solid #ddd;
            padding: 20px;
        }
        
        .left-sidebar h3 {
            margin-top: 0;
        }
        
        .content {
            padding: 20px;
        }
        
        .nav-item {
            padding: 10px;
            background: #f8f9fa;
            margin: 5px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <header class="topbar">
        <button class="hamburger" id="headerCollapse" title="Toggle Sidebar">☰</button>
        <h1>Test Page</h1>
    </header>

    <aside class="left-sidebar">
        <h3>Sidebar Menu</h3>
        <div class="nav-item">Home</div>
        <div class="nav-item">Dashboard</div>
        <div class="nav-item">Settings</div>
        <div class="nav-item">Profile</div>
        <div class="nav-item">Logout</div>
    </aside>

    <main class="content">
        <h2>Welcome!</h2>
        <p>Click the hamburger menu (☰) button to toggle the sidebar on mobile.</p>
        <p>Resize your window to test responsiveness.</p>
        <p>Current screen width: <span id="width"></span>px</p>
    </main>

    <script>
        // Update width display
        function updateWidth() {
            document.getElementById('width').textContent = window.innerWidth;
        }
        updateWidth();
        window.addEventListener('resize', updateWidth);

        // Sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== Sidebar Toggle Test ===');
            
            const sidebarToggler = document.getElementById('headerCollapse');
            const sidebar = document.querySelector('.left-sidebar');
            const body = document.body;
            
            console.log('Sidebar element found:', !!sidebar);
            console.log('Toggler button found:', !!sidebarToggler);
            
            if (!sidebar || !sidebarToggler) {
                console.error('Required elements not found!');
                return;
            }
            
            // Create overlay
            let overlay = document.querySelector('.sidebar-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'sidebar-overlay';
                body.appendChild(overlay);
                console.log('Overlay created');
            }
            
            // Toggle function
            function toggleSidebar() {
                console.log('Toggle called');
                sidebar.classList.toggle('show-sidebar');
                overlay.classList.toggle('show');
                console.log('show-sidebar active:', sidebar.classList.contains('show-sidebar'));
            }
            
            // Click handler
            sidebarToggler.addEventListener('click', function(e) {
                console.log('Button clicked');
                e.preventDefault();
                e.stopPropagation();
                toggleSidebar();
            });
            
            // Overlay click
            overlay.addEventListener('click', function(e) {
                console.log('Overlay clicked');
                e.preventDefault();
                e.stopPropagation();
                toggleSidebar();
            });
            
            console.log('Sidebar toggle initialized successfully');
        });
    </script>
</body>
</html>
