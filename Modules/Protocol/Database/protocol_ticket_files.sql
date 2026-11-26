CREATE TABLE protocol_ticket_files (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    protocol_request_id BIGINT UNSIGNED NOT NULL,
    uploaded_by_user_id BIGINT UNSIGNED NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(255) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_protocol_ticket_files_request (protocol_request_id),
    CONSTRAINT fk_protocol_ticket_files_request
        FOREIGN KEY (protocol_request_id)
        REFERENCES protocol_requests (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
