{{-- ===================== --}}
{{-- Conversation Messages --}}
{{-- ===================== --}}
<style>
    :root {
        --lbsnaa-blue: #004a93;
        --lbsnaa-blue-dark: #003366;
        --lbsnaa-blue-light: #e0eafc;
        --lbsnaa-orange: #ff6b35;
        --lbsnaa-orange-light: #fff1eb;
    }

    .chat-wrapper {
        display: flex;
        flex-direction: column;
        max-height: 85vh;
        min-height: 80vh;
        height: 100%;
        overflow: hidden;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .chat-header-bar {
        background: linear-gradient(135deg, var(--lbsnaa-blue) 0%, var(--lbsnaa-blue-dark) 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px 12px 0 0;
    }

    .chat-container {
        padding: 1.25rem 1.5rem;
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        background: #f8fafc;
        scroll-behavior: smooth;
    }

    /* Enhanced scrollbar */
    .chat-container::-webkit-scrollbar {
        width: 8px;
    }

    .chat-container::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    .chat-container::-webkit-scrollbar-thumb {
        background: rgba(0, 74, 147, 0.3);
        border-radius: 4px;
        border: 2px solid #f1f5f9;
    }

    .chat-container::-webkit-scrollbar-thumb:hover {
        background: rgba(0, 74, 147, 0.5);
    }

    /* Chat rows */
    .chat-row {
        display: flex;
        margin-bottom: 1rem;
        animation: fadeInUp 0.3s ease;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .chat-left {
        justify-content: flex-start;
    }

    .chat-right {
        justify-content: flex-end;
    }

    /* Modern chat bubbles */
    .chat-bubble {
        max-width: 75%;
        padding: 0.875rem 1.125rem;
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.1);
        position: relative;
        font-size: 0.9375rem;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* Receiver bubble */
    .chat-left .chat-bubble {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
    }

    /* Sender bubble - LBSNAA theme */
    .chat-right .chat-bubble {
        background: linear-gradient(135deg, var(--lbsnaa-blue) 0%, var(--lbsnaa-blue-dark) 100%);
        color: white;
        border: none;
    }

    /* Bubble tail effect */
    .chat-left .chat-bubble::before,
    .chat-right .chat-bubble::before {
        content: "";
        position: absolute;
        bottom: 0;
        width: 12px;
        height: 12px;
        background: inherit;
    }

    .chat-left .chat-bubble::before {
        left: -6px;
        border-radius: 0 0 0 4px;
        border-left: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
    }

    .chat-right .chat-bubble::before {
        right: -6px;
        border-radius: 0 0 4px 0;
        border-right: 1px solid var(--lbsnaa-blue-dark);
        border-bottom: 1px solid var(--lbsnaa-blue-dark);
    }

    /* Chat header */
    .chat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.375rem;
        font-size: 0.75rem;
    }

    .chat-user {
        font-weight: 600;
        color: var(--lbsnaa-blue);
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .chat-user i {
        font-size: 0.875rem;
    }

    .chat-right .chat-user {
        color: rgba(255, 255, 255, 0.95);
    }

    .chat-time {
        color: #94a3b8;
        font-size: 0.6875rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .chat-right .chat-time {
        color: rgba(255, 255, 255, 0.8);
    }

    /* Chat text */
    .chat-text {
        font-size: 0.9375rem;
        line-height: 1.5;
        color: #1f2937;
        white-space: pre-wrap;
        word-wrap: break-word;
        hyphens: auto;
    }

    .chat-right .chat-text {
        color: rgba(255, 255, 255, 0.95);
    }

    /* Enhanced attachment styling */
    .chat-attachment {
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px dashed rgba(0, 0, 0, 0.1);
    }

    .chat-right .chat-attachment {
        border-top-color: rgba(255, 255, 255, 0.2);
    }

    .attachment-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.875rem;
        background: rgba(255, 107, 53, 0.1);
        border-radius: 8px;
        color: var(--lbsnaa-orange);
        text-decoration: none;
        font-size: 0.8125rem;
        font-weight: 500;
        transition: all 0.2s ease;
        border: 1px solid rgba(255, 107, 53, 0.2);
    }

    .attachment-link:hover {
        background: rgba(255, 107, 53, 0.2);
        transform: translateY(-1px);
        text-decoration: none;
        color: var(--lbsnaa-orange);
    }

    .attachment-link i {
        font-size: 1rem;
    }

    /* Message input field with integrated buttons */
    .message-composer {
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        padding: 1rem;
        position: relative;
    }

    .composer-wrapper {
        display: flex;
        align-items: flex-end;
        gap: 0.75rem;
        background: #f8fafc;
        border-radius: 50px;
        padding: 0.5rem;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }

    .composer-wrapper:focus-within {
        border-color: var(--lbsnaa-blue);
        box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.1);
        background: #ffffff;
    }

    /* Attachment area */
    .attachment-preview {
        position: absolute;
        bottom: 100%;
        left: 0;
        right: 0;
        background: white;
        border-radius: 12px 12px 0 0;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        display: none;
        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05);
    }

    .attachment-preview.show {
        display: block;
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            transform: translateY(10px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .attachment-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.8125rem;
        color: #475569;
    }

    .attachment-info i {
        color: var(--lbsnaa-orange);
    }

    .remove-attachment {
        margin-left: auto;
        background: none;
        border: none;
        color: #94a3b8;
        padding: 0.25rem;
        border-radius: 4px;
        transition: all 0.2s ease;
    }

    .remove-attachment:hover {
        color: #ef4444;
        background: rgba(239, 68, 68, 0.1);
    }

    /* Integrated textarea */
    .message-input {
        flex: 1;
        border: none;
        background: transparent;
        resize: none;
        padding: 0.625rem 0.75rem;
        font-size: 0.9375rem;
        line-height: 1.4;
        max-height: 120px;
        min-height: 40px;
        outline: none;
    }

    .message-input::placeholder {
        color: #94a3b8;
    }

    /* Integrated action buttons */
    .action-buttons {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .action-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border: 1px solid #e2e8f0;
        color: #64748b;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .action-btn:hover {
        background: #f1f5f9;
        color: var(--lbsnaa-blue);
        border-color: #cbd5e1;
    }

    .action-btn:focus {
        outline: 2px solid var(--lbsnaa-blue);
        outline-offset: 2px;
    }

    .send-btn {
        background: var(--lbsnaa-blue);
        border: none;
        color: white;
        width: 40px;
        height: 40px;
    }

    .send-btn:hover {
        background: var(--lbsnaa-blue-dark);
        transform: scale(1.05);
    }

    .send-btn:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
        transform: none;
    }

    /* Status indicators */
    .status-badge {
        font-size: 0.6875rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-weight: 500;
    }

    .status-open {
        background: rgba(34, 197, 94, 0.1);
        color: #16a34a;
    }

    .status-closed {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
    }

    .empty-state-icon {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }

    .empty-state-text {
        color: #64748b;
        font-size: 0.9375rem;
    }

    /* Accessibility focus styles */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .chat-wrapper {
            max-height: 80vh;
            min-height: 70vh;
            border-radius: 0;
        }

        .chat-container {
            padding: 1rem;
        }

        .chat-bubble {
            max-width: 85%;
            padding: 0.75rem 1rem;
        }

        .composer-wrapper {
            border-radius: 25px;
        }

        .action-btn {
            width: 36px;
            height: 36px;
        }
    }

    @media (max-width: 480px) {
        .chat-header-bar {
            padding: 0.75rem 1rem;
        }

        .message-composer {
            padding: 0.75rem;
        }

        .composer-wrapper {
            gap: 0.5rem;
        }

        .message-input {
            font-size: 0.875rem;
            padding: 0.5rem;
        }
    }

    /* Print styles */
    @media print {
        .chat-wrapper {
            box-shadow: none;
            border: 1px solid #dee2e6;
        }

        .message-composer {
            display: none;
        }

        .chat-container {
            max-height: none;
            overflow: visible;
        }
    }
</style>

<div class="chat-wrapper" role="region" aria-label="Conversation chat">
    <!-- Chat Header -->
    <div class="chat-header-bar">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h5 mb-1 text-white">
                    <i class="material-icons me-2" aria-hidden="true">chat</i>
                    Conversation
                </h2>
                <p class="small text-white-80 mb-0">
                    @if($conversations->isNotEmpty())
                        <span class="badge status-badge {{ $conversations->last()->notice_status == 2 ? 'status-open' : 'status-closed' }}">
                            {{ $conversations->last()->notice_status == 2 ? 'Open' : 'Closed' }}
                        </span>
                        <span class="ms-2">Last message: {{ \Carbon\Carbon::parse($conversations->last()->created_date)->format('M d, h:i A') }}</span>
                    @else
                        No messages yet
                    @endif
                </p>
            </div>
            <button type="button" class="btn btn-sm btn-outline-light" onclick="scrollToBottom()" aria-label="Scroll to latest messages">
                <i class="material-icons" aria-hidden="true">arrow_downward</i>
            </button>
        </div>
    </div>

    <!-- Messages Container -->
    <div class="chat-container" id="chatMessages" role="log" aria-live="polite" aria-relevant="additions">
        @forelse ($conversations as $msg)
            <div class="chat-row {{ $type == 'student' ? ($msg->user_type == 'admin' ? 'chat-left' : 'chat-right') : ($msg->user_type == 'admin' ? 'chat-right' : 'chat-left') }}"
                 role="listitem"
                 aria-label="Message from {{ $msg->display_name }} at {{ \Carbon\Carbon::parse($msg->created_date)->format('h:i A') }}">
                
                <div class="chat-bubble">
                    <div class="chat-header">
                        <span class="chat-user">
                            @if($msg->user_type == 'admin')
                                <i class="material-icons" aria-hidden="true">school</i>
                            @else
                                <i class="material-icons" aria-hidden="true">person</i>
                            @endif
                            {{ $msg->display_name }}
                        </span>
                        <span class="chat-time">
                            <i class="material-icons" aria-hidden="true">schedule</i>
                            {{ \Carbon\Carbon::parse($msg->created_date)->format('d M Y, h:i A') }}
                        </span>
                    </div>

                    <div class="chat-text" role="text">
                        {{ $msg->student_decip_incharge_msg }}
                    </div>

                    @if (!empty($msg->doc_upload))
                        <div class="chat-attachment" role="group" aria-label="Attachment">
                            <a href="{{ asset('storage/' . $msg->doc_upload) }}" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="attachment-link"
                               aria-label="View attachment {{ basename($msg->doc_upload) }}">
                                <i class="material-icons" aria-hidden="true">attach_file</i>
                                <span>View Attachment</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="empty-state" role="status" aria-live="polite">
                <div class="empty-state-icon">
                    <i class="material-icons" aria-hidden="true">chat</i>
                </div>
                <p class="empty-state-text">No conversation yet. Start the discussion!</p>
            </div>
        @endforelse
    </div>

    <!-- Message Input Form (only show when conversation is open) -->
    @if($conversations->isNotEmpty())
        @if($conversations->last()->notice_status == 2)
            <form id="memo_notice_conversation"
                  method="POST"
                  enctype="multipart/form-data"
                  action="{{ route('memo.discipline.conversation.store') }}"
                  class="message-composer"
                  aria-label="Send message form">
                @csrf

                <input type="hidden" name="memo_discipline_id" value="{{ $memoId }}">
                <input type="hidden" name="type" value="{{ $type }}">

                @if ($type == 'OT')
                    <input type="hidden" name="created_by" value="@if(isset($conversations->first()->student_id )) {{ $conversations->first()->student_id }} @else {{ auth()->user()->user_id }} @endif">
                    <input type="hidden" name="role_type" value="s">
                @else
                    <input type="hidden" name="created_by" value="{{ auth()->user()->pk }}">
                    <input type="hidden" name="role_type" value="f">
                @endif

                <!-- Attachment Preview -->
                <div class="attachment-preview" id="attachmentPreview" role="region" aria-label="Attachment preview">
                    <div class="attachment-info">
                        <i class="material-icons" aria-hidden="true">attach_file</i>
                        <span id="fileName">No file selected</span>
                        <button type="button" class="remove-attachment" onclick="removeAttachment()" aria-label="Remove attachment">
                            <i class="material-icons" aria-hidden="true">close</i>
                        </button>
                    </div>
                </div>

                <!-- Integrated Input Area -->
                <div class="composer-wrapper">
                    <!-- Attachment Button -->
                    <button type="button" 
                            class="action-btn" 
                            onclick="document.getElementById('fileInput').click()"
                            aria-label="Attach file">
                        <i class="material-icons" aria-hidden="true">attach_file</i>
                        <input type="file" 
                               name="attachment" 
                               id="fileInput" 
                               hidden 
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                               onchange="previewAttachment(this)">
                    </button>

                    <!-- Text Input -->
                    <textarea name="student_decip_incharge_msg"
                              class="message-input"
                              rows="1"
                              placeholder="Type your message..."
                              required
                              aria-label="Type your message"
                              oninput="autoResize(this)"
                              onkeydown="handleEnterKey(event)"></textarea>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <!-- Emoji Button (Optional) -->
                        <button type="button" class="action-btn" aria-label="Insert emoji">
                            <i class="material-icons" aria-hidden="true">mood</i>
                        </button>

                        <!-- Send Button -->
                        <button type="submit" 
                                class="action-btn send-btn" 
                                aria-label="Send message"
                                id="sendButton">
                            <i class="material-icons" aria-hidden="true">send</i>
                        </button>
                    </div>
                </div>

                <!-- Hidden file input for proper form submission -->
                <input type="file" name="attachment" id="hiddenFileInput" hidden>
            </form>

        @elseif($conversations->last()->notice_status == 3)
            <div class="alert alert-warning alert-dismissible fade show m-3" role="alert" aria-live="assertive">
                <div class="d-flex align-items-center">
                    <i class="material-icons me-2" aria-hidden="true">lock</i>
                    <div>
                        <strong class="d-block">Memo Closed</strong>
                        <p class="mb-0">This conversation has been closed. You cannot reply further.</p>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info m-3 text-center" role="status" aria-live="polite">
                <i class="material-icons me-1" aria-hidden="true">info</i>
                Memo has not started yet.
            </div>
        @endif
    @endif
</div>

<script>
    // Auto-resize textarea
    function autoResize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        
        // Enable/disable send button based on content
        const sendBtn = document.getElementById('sendButton');
        if (sendBtn) {
            sendBtn.disabled = textarea.value.trim() === '' && !document.getElementById('fileInput').files[0];
        }
    }

    // Preview attachment
    function previewAttachment(input) {
        const preview = document.getElementById('attachmentPreview');
        const fileName = document.getElementById('fileName');
        
        if (input.files && input.files[0]) {
            const file = input.files[0];
            fileName.textContent = file.name;
            preview.classList.add('show');
            
            // Update hidden input
            const hiddenInput = document.getElementById('hiddenFileInput');
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            hiddenInput.files = dataTransfer.files;
            
            // Enable send button
            const sendBtn = document.getElementById('sendButton');
            if (sendBtn) sendBtn.disabled = false;
        }
    }

    // Remove attachment
    function removeAttachment() {
        const preview = document.getElementById('attachmentPreview');
        const fileInput = document.getElementById('fileInput');
        const hiddenInput = document.getElementById('hiddenFileInput');
        
        fileInput.value = '';
        hiddenInput.value = '';
        preview.classList.remove('show');
        
        // Check if send button should be disabled
        const textarea = document.querySelector('.message-input');
        const sendBtn = document.getElementById('sendButton');
        if (sendBtn && textarea.value.trim() === '') {
            sendBtn.disabled = true;
        }
    }

    // Handle Enter key (send on Ctrl+Enter, new line on Enter)
    function handleEnterKey(event) {
        if (event.key === 'Enter' && event.ctrlKey) {
            event.preventDefault();
            document.getElementById('sendButton').click();
        }
    }

    // Scroll to bottom of chat
    function scrollToBottom() {
        const container = document.getElementById('chatMessages');
        if (container) {
            container.scrollTo({
                top: container.scrollHeight,
                behavior: 'smooth'
            });
        }
    }

    // Auto-scroll to bottom when page loads
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(scrollToBottom, 100);
        
        // Focus on textarea when form is available
        const textarea = document.querySelector('.message-input');
        if (textarea) {
            textarea.focus();
        }
    });

    // Form submission handler
    document.getElementById('memo_notice_conversation')?.addEventListener('submit', function(e) {
        const textarea = this.querySelector('textarea[name="student_decip_incharge_msg"]');
        const fileInput = this.querySelector('input[name="attachment"]');
        
        if (!textarea.value.trim() && !fileInput.files[0]) {
            e.preventDefault();
            alert('Please enter a message or attach a file');
            textarea.focus();
        }
    });

    // Accessibility: Announce new messages
    function announceNewMessage(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('role', 'alert');
        announcement.setAttribute('aria-live', 'assertive');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = 'New message received: ' + message;
        document.body.appendChild(announcement);
        
        // Remove after announcement
        setTimeout(() => announcement.remove(), 1000);
    }

    // Simulate new message for demo (remove in production)
    if (window.location.href.includes('demo')) {
        setInterval(() => {
            const messages = ['System notification', 'New update available'];
            const randomMsg = messages[Math.floor(Math.random() * messages.length)];
            announceNewMessage(randomMsg);
        }, 30000);
    }
</script>