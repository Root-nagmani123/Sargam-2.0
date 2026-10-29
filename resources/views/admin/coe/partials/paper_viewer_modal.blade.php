{{-- COE question papers — read-only paper viewer (See Attachment / View … Paper).
     Behaviour is CoePaperViewer in public/js/coe-paper-viewer.js: no download,
     print or copy, and the viewer's name stamped across the page. --}}
<div class="modal fade" id="coePaperViewer" tabindex="-1" aria-labelledby="coePaperViewerTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content coe-modal coe-viewer border-0 shadow">
            <div class="modal-header coe-viewer__head">
                <div class="coe-viewer__heading">
                    <h5 class="modal-title fw-bold" id="coePaperViewerTitle">Question Paper</h5>
                    <p class="coe-viewer__sub" id="coePaperViewerSub"></p>
                </div>
                <span class="coe-viewer__badge"><i class="bi bi-shield-lock" aria-hidden="true"></i>View only</span>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body coe-viewer__body">
                <p class="coe-viewer__state" id="coePaperViewerState" role="status"></p>
                <div class="coe-viewer__stage" id="coePaperViewerStage" hidden>
                    <div class="coe-viewer__sheet" id="coePaperViewerSheet"></div>
                    <div class="coe-viewer__mark" id="coePaperViewerMark" aria-hidden="true"></div>
                </div>
            </div>
            {{-- Covers the paper while the window is not in use (screen capture tools, Alt+Tab). --}}
            <div class="coe-viewer__shield" aria-hidden="true">
                <i class="bi bi-eye-slash" aria-hidden="true"></i>
                <p>The paper is hidden while this window is not in use.<br>Click here to continue.</p>
            </div>
        </div>
    </div>
</div>
