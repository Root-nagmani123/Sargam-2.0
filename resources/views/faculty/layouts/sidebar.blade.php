
<!-- Tab Content Container -->

<!-- //admin tabs -->
    <div class="tab-content" id="mainNavbarContent">
        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
           @include('admin.layouts.sidebar.home')
        </div>
        <div class="tab-pane fade" id="tab-communications" role="tabpanel" aria-labelledby="communications-tab">
            @include('admin.layouts.sidebar.communication') 
        </div>
        <div class="tab-pane fade" id="tab-academics" role="tabpanel" aria-labelledby="academics-tab">
            @include('admin.layouts.sidebar.academics')
        </div>
        <div class="tab-pane fade" id="tab-purchase-order" role="tabpanel" aria-labelledby="purchase-order-tab">
            @include('admin.layouts.sidebar.material')
        </div>
    </div>


    <!-- //faculty & OTs tabs -->



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