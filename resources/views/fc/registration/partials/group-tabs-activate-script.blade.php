<script>
(function () {
    function tabListRoot() {
        return document.getElementById('step3Tabs') || document.getElementById('groupTabs');
    }

    function tabContentRoot() {
        return document.getElementById('step3TabContent') || document.getElementById('groupTabContent');
    }

    function groupNameFromTarget(target) {
        if (!target || target.charAt(0) !== '#') {
            return '';
        }
        return target.indexOf('#tab-') === 0 ? target.slice(5) : target.slice(1);
    }

    function activateGroupTab(groupName) {
        if (!groupName) {
            return;
        }

        var btn = document.getElementById('tab-' + groupName + '-btn');
        var pane = document.getElementById('tab-' + groupName);
        var tabList = tabListRoot();
        var tabContent = tabContentRoot();

        if (!btn || !pane || !tabList || !tabContent) {
            return;
        }

        tabList.querySelectorAll('.nav-link').forEach(function (link) {
            var on = link === btn;
            link.classList.toggle('active', on);
            link.setAttribute('aria-selected', on ? 'true' : 'false');
        });

        tabContent.querySelectorAll('.tab-pane').forEach(function (p) {
            var on = p === pane;
            p.classList.toggle('show', on);
            p.classList.toggle('active', on);
        });
    }

    function bootFcGroupTabs() {
        var params = new URLSearchParams(window.location.search);
        var group = params.get('group');
        if (group) {
            activateGroupTab(group);
            return;
        }

        var firstBtn = tabListRoot() && tabListRoot().querySelector('.nav-link');
        if (firstBtn) {
            activateGroupTab(groupNameFromTarget(firstBtn.getAttribute('data-bs-target') || ''));
        }
    }

    function scheduleBoot() {
        bootFcGroupTabs();
        setTimeout(bootFcGroupTabs, 0);
        setTimeout(bootFcGroupTabs, 100);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleBoot);
    } else {
        scheduleBoot();
    }
})();
</script>
