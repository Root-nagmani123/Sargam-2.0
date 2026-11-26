ALTER TABLE `protocol_ticket_journeys` ADD `meal_type` VARCHAR(155) NULL AFTER `book_if_waiting`;
ALTER TABLE `protocol_request_guests` ADD `salutation` VARCHAR(20) NULL AFTER `batch_id`;

CREATE TABLE protocol_request_drivers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    protocol_request_id BIGINT UNSIGNED NOT NULL,
    vehicle_no VARCHAR(100) NOT NULL,
    vehicle_name VARCHAR(150) NOT NULL,
    driver_name VARCHAR(150) NOT NULL,
    driver_number VARCHAR(30) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_protocol_request_drivers_request (protocol_request_id),
    CONSTRAINT fk_protocol_request_drivers_request
        FOREIGN KEY (protocol_request_id)
        REFERENCES protocol_requests (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
