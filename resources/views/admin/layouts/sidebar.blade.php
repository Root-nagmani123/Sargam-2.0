<!-- Sidebar Start -->
<div class="tab-content mt-4">
    <div class="tab-pane active" id="tab-home">
       @include('admin.layouts.sidebar.home')
    </div>
    <div class="tab-pane d-none" id="tab-setup">
        @include('admin.layouts.sidebar.setup')
    </div>
    <div class="tab-pane d-none" id="tab-communication">
        @include('admin.layouts.sidebar.communication')
    </div>
    <div class="tab-pane d-none" id="tab-academics">
        @include('admin.layouts.sidebar.academics')
    </div>
</div>

<script>
const sidebar = document.querySelector('.sidebarmenu .simplebar-content-wrapper');

// Restore scroll position on load
document.addEventListener('DOMContentLoaded', function() {
    const scrollPos = localStorage.getItem('sidebar-scroll');
    if (scrollPos && sidebar) {
        sidebar.scrollTop = parseInt(scrollPos, 10);
    }
});

// Save scroll position before unload
window.addEventListener('beforeunload', function() {
    if (sidebar) {
        localStorage.setItem('sidebar-scroll', sidebar.scrollTop);
    }
});
// Add on click
document.querySelectorAll('.mini-nav-item').forEach(item => {
    item.addEventListener('click', function() {
        localStorage.setItem('active-mini-nav', this.id);
    });
});

// On load
document.addEventListener('DOMContentLoaded', () => {
    const activeId = localStorage.getItem('active-mini-nav');
    if (activeId) {
        document.querySelectorAll('.mini-nav-item').forEach(item => item.classList.remove('selected'));
        const activeEl = document.getElementById(activeId);
        if (activeEl) activeEl.classList.add('selected');
    }
});
</script>

<!--  Sidebar End -->