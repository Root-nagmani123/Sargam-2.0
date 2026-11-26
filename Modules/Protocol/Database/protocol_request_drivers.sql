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
